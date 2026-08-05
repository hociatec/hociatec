<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Application\Factory\InvoiceNumberGenerator;
use App\Module\Order\Application\Factory\OrderNumberGenerator;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class CheckoutSessionOrderCreator
{
    public function __construct(
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        private OrderNumberGenerator $numberGenerator,
        private InvoiceNumberGenerator $invoiceNumberGenerator,
        private OrderInvoiceCalculator $invoiceCalculator,
        private OrderRepositoryPort $orders,
        private ProductCatalogRepository $products,
        private CartSessionRepositoryPort $carts,
    ) {
    }

    public function create(OrderCheckoutSession $checkout): Order
    {
        return $this->transactions->transactional(
            function () use ($checkout): Order {
                if (null !== $checkout->getOrderId()) {
                    $existing = $this->orders->findForUpdate($checkout->getOrderId());
                    if ($existing instanceof Order) {
                        return $existing;
                    }
                }

                $order = $this->createOrder($checkout);
                foreach ($checkout->getItemsPayload() as $rawItem) {
                    $this->addItem($order, $rawItem);
                }

                $this->invoiceCalculator->snapshot($order);
                $this->persistence->persist($order);
                $this->persistence->commit();
                if (null === $order->getId()) {
                    throw new \InvalidArgumentException('Commande invalide.');
                }

                $this->markCartConverted($checkout, $order->getId());
                $checkout->setOrderId($order->getId());
                $this->persistence->persist($checkout);
                $this->persistence->commit();

                return $order;
            },
        );
    }

    private function createOrder(OrderCheckoutSession $checkout): Order
    {
        return (new Order($this->numberGenerator->generate(), $checkout->getUser()))
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setShippingName($checkout->getShippingName())
            ->setShippingAddress($checkout->getShippingAddress())
            ->setShippingPostalCode($checkout->getShippingPostalCode())
            ->setShippingCity($checkout->getShippingCity())
            ->setBillingName($checkout->getBillingName())
            ->setBillingCompany($checkout->getBillingCompany())
            ->setBillingCompanySiren($checkout->getBillingCompanySiren())
            ->setBillingCompanyVatNumber($checkout->getBillingCompanyVatNumber())
            ->setPurchaseOrderNumber($checkout->getPurchaseOrderNumber())
            ->setBillingAddress($checkout->getBillingAddress())
            ->setBillingPostalCode($checkout->getBillingPostalCode())
            ->setBillingCity($checkout->getBillingCity())
            ->setBillingEmail($checkout->getBillingEmail())
            ->setInvoiceNumber($this->invoiceNumberGenerator->generate())
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable())
            ->setCurrencyCode($checkout->getCurrencyCode())
            ->setElectronicFormat('UBL-2.1')
            ->setSubtotalPriceCents($checkout->getSubtotalPriceCents())
            ->setDiscountAmountCents($checkout->getDiscountAmountCents())
            ->setTotalPriceCents($checkout->getTotalPriceCents())
            ->setAppliedPromotionName($checkout->getAppliedPromotionName())
            ->setAppliedPromotionSlug($checkout->getAppliedPromotionSlug());
    }

    /** @param array<string, mixed> $rawItem */
    private function addItem(Order $order, array $rawItem): void
    {
        $productId = (int) ($rawItem['productId'] ?? 0);
        if ($productId <= 0) {
            throw new \InvalidArgumentException('Produit Stripe invalide.');
        }

        $product = $this->products->findForUpdate($productId);
        if (null === $product) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }

        $quantity = max(1, (int) ($rawItem['quantity'] ?? 1));
        if ($quantity > $product->getStock()) {
            throw new \InvalidArgumentException('Stock insuffisant pour le produit '.$product->getSku().'.');
        }
        $product->setStock($product->getStock() - $quantity);

        $item = (new OrderItem(
            (string) ($rawItem['productName'] ?? $product->getName()),
            (string) ($rawItem['productSku'] ?? $product->getSku()),
            max(0, (int) ($rawItem['unitPriceCents'] ?? $product->getPriceCents())),
            $quantity,
        ))
            ->setProduct($product)
            ->setVatRateBps(max(0, (int) ($rawItem['vatRateBps'] ?? 2000)));
        $order->addItem($item);
        $this->persistence->persist($item);
    }

    private function markCartConverted(
        OrderCheckoutSession $checkout,
        int $orderId,
    ): void {
        if (null === $checkout->getCartId()) {
            return;
        }

        $cart = $this->carts->findForUpdate($checkout->getCartId());
        if (null !== $cart && !$cart->isConverted()) {
            $cart->markConverted($orderId);
            $this->persistence->persist($cart);
            $this->persistence->commit();
        }
    }
}
