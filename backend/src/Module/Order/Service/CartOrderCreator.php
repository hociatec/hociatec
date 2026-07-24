<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Product;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\Promotion\Service\PromotionEngine;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\Voucher\Service\VoucherEngine;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CartOrderCreator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderNumberGenerator $numberGenerator,
        private InvoiceNumberGenerator $invoiceNumberGenerator,
        private OrderInvoiceCalculator $invoiceCalculator,
        private PromotionEngine $promotionEngine,
        private VoucherEngine $voucherEngine,
    ) {
    }

    public function create(User $user, CartSession $cart, ShippingAddress $address): Order
    {
        if (0 === $cart->getItems()->count()) {
            throw new \InvalidArgumentException('Le panier est vide.');
        }

        $summary = $this->cartSummary($cart, $user);

        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use ($user, $cart, $address, $summary): Order {
                $cartId = $cart->getId();
                if (null === $cartId) {
                    throw new \InvalidArgumentException('Panier invalide.');
                }

                $lockedCart = $em->find(CartSession::class, $cartId, LockMode::PESSIMISTIC_WRITE);
                if (!$lockedCart instanceof CartSession) {
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

                    $product = $em->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
                    if (!$product instanceof Product) {
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
                    $em->persist($item);
                }

                $this->invoiceCalculator->snapshot($order);
                $em->persist($order);
                $em->flush();

                if (null === $order->getId()) {
                    throw new \InvalidArgumentException('Commande invalide.');
                }
                $lockedCart->markConverted($order->getId());
                $em->persist($lockedCart);
                $em->flush();

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
