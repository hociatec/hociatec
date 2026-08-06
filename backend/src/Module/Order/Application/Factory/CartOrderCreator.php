<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Calculator\VoucherEngine;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class CartOrderCreator
{
    public function __construct(
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        private OrderNumberGenerator $numberGenerator,
        private InvoiceNumberGenerator $invoiceNumberGenerator,
        private OrderInvoiceCalculator $invoiceCalculator,
        private PromotionEngine $promotionEngine,
        private VoucherEngine $voucherEngine,
        private CartSessionRepositoryPort $carts,
        private ProductCatalogRepository $products,
    ) {
    }

    public function create(User $user, CartSession $cart, ShippingAddress $address): Order
    {
        if (0 === $cart->getItems()->count()) {
            throw new \InvalidArgumentException('Le panier est vide.');
        }

        $summary = $this->cartSummary($cart, $user);

        return $this->transactions->transactional(
            function () use ($user, $cart, $address, $summary): Order {
                $cartId = $cart->getId();
                if (null === $cartId) {
                    throw new \InvalidArgumentException('Panier invalide.');
                }

                $lockedCart = $this->carts->findForUpdate($cartId);
                if (null === $lockedCart) {
                    throw new \InvalidArgumentException('Panier introuvable.');
                }
                if ($lockedCart->isConverted()) {
                    throw new \InvalidArgumentException('Ce panier a deja ete valide.');
                }
                if (0 === $lockedCart->getItems()->count()) {
                    throw new \InvalidArgumentException('Le panier est vide.');
                }

                $order = $this->createOrder($user, $address, $summary);
                foreach ($lockedCart->getItems() as $cartItem) {
                    $productId = $cartItem->getProduct()->getId();
                    if (null === $productId) {
                        throw new \InvalidArgumentException('Produit invalide.');
                    }

                    $product = $this->products->findForUpdate($productId);
                    if (null === $product) {
                        throw new \InvalidArgumentException('Produit introuvable.');
                    }

                    $quantity = $cartItem->getQuantity();
                    if ($quantity > $product->getStock()) {
                        throw new \InvalidArgumentException('Stock insuffisant pour le produit '.$product->getSku().'.');
                    }
                    $product->setStock($product->getStock() - $quantity);

                    $item = (new OrderItem($product->getName(), $product->getSku(), $product->getPriceCents(), $quantity))
                        ->setProduct($product)
                        ->setVatRateBps(2000);
                    $order->addItem($item);
                    $this->persistence->persist($item);
                }

                $this->invoiceCalculator->snapshot($order);
                $this->persistence->persist($order);
                $this->persistence->commit();

                if (null === $order->getId()) {
                    throw new \InvalidArgumentException('Commande invalide.');
                }
                $lockedCart->markConverted($order->getId());
                $this->persistence->persist($lockedCart);
                $this->persistence->commit();

                return $order;
            },
        );
    }

    /** @param array<string, mixed> $summary */
    private function createOrder(User $user, ShippingAddress $address, array $summary): Order
    {
        $customerName = trim($user->getFirstName().' '.$user->getLastName());

        return (new Order($this->numberGenerator->generate(), $user))
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setShippingName('' !== $customerName ? $customerName : $address->getName())
            ->setShippingAddress($address->getAddress())
            ->setShippingPostalCode($address->getPostalCode())
            ->setShippingCity($address->getCity())
            ->setBillingName('' !== $customerName ? $customerName : $address->getName())
            ->setBillingCompany($address->getCompany())
            ->setBillingCompanySiren($address->getCompanySiren())
            ->setBillingCompanyVatNumber($address->getCompanyVatNumber())
            ->setPurchaseOrderNumber($address->getPurchaseOrderNumber())
            ->setBillingAddress($address->getAddress())
            ->setBillingPostalCode($address->getPostalCode())
            ->setBillingCity($address->getCity())
            ->setBillingEmail($user->getEmail())
            ->setInvoiceNumber($this->invoiceNumberGenerator->generate())
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable())
            ->setCurrencyCode('EUR')
            ->setElectronicFormat('UBL-2.1')
            ->setSubtotalPriceCents((int) $summary['subtotalPriceCents'])
            ->setDiscountAmountCents((int) $summary['discountAmountCents'])
            ->setTotalPriceCents((int) $summary['totalPriceCents'])
            ->setAppliedPromotionName($summary['appliedVoucher']['name'] ?? ($summary['appliedPromotion']['name'] ?? null))
            ->setAppliedPromotionSlug($summary['appliedVoucher']['code'] ?? ($summary['appliedPromotion']['slug'] ?? null));
    }

    /** @return array<string, mixed> */
    private function cartSummary(CartSession $cart, User $user): array
    {
        $promotion = $this->promotionEngine->calculateCartSummary($cart, $user);
        $voucher = $this->voucherEngine->calculateCartSummary($cart, $user, $cart->getVoucherCode());

        return null !== $cart->getVoucherCode() && 'applied' === $voucher['voucherCodeStatus']
            ? $voucher
            : $promotion;
    }
}
