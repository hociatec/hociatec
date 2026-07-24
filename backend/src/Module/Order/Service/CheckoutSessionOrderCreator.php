<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Product;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Entity\OrderItem;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CheckoutSessionOrderCreator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderNumberGenerator $numberGenerator,
        private InvoiceNumberGenerator $invoiceNumberGenerator,
        private OrderInvoiceCalculator $invoiceCalculator,
    ) {
    }

    public function create(OrderCheckoutSession $checkout): Order
    {
        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use ($checkout): Order {
                if (null !== $checkout->getOrderId()) {
                    $existing = $em->find(Order::class, $checkout->getOrderId());
                    if ($existing instanceof Order) {
                        return $existing;
                    }
                }

                $order = $this->createOrder($checkout);
                foreach ($checkout->getItemsPayload() as $rawItem) {
                    $this->addItem($em, $order, $rawItem);
                }

                $this->invoiceCalculator->snapshot($order);
                $em->persist($order);
                $em->flush();
                if (null === $order->getId()) {
                    throw new \InvalidArgumentException('Commande invalide.');
                }

                $this->markCartConverted($em, $checkout, $order->getId());
                $checkout->setOrderId($order->getId());
                $em->persist($checkout);
                $em->flush();

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
    private function addItem(EntityManagerInterface $em, Order $order, array $rawItem): void
    {
        $productId = (int) ($rawItem['productId'] ?? 0);
        if ($productId <= 0) {
            throw new \InvalidArgumentException('Produit Stripe invalide.');
        }

        $product = $em->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
        if (!$product instanceof Product) {
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
        $em->persist($item);
    }

    private function markCartConverted(
        EntityManagerInterface $em,
        OrderCheckoutSession $checkout,
        int $orderId,
    ): void {
        if (null === $checkout->getCartId()) {
            return;
        }

        $cart = $em->find(CartSession::class, $checkout->getCartId(), LockMode::PESSIMISTIC_WRITE);
        if ($cart instanceof CartSession && !$cart->isConverted()) {
            $cart->markConverted($orderId);
            $em->persist($cart);
            $em->flush();
        }
    }
}
