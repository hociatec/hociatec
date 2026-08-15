<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\Workflow;

use App\Module\Order\Application\Handler\StripeCheckoutSessionExpirer;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderItemRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Support\RentalPeriodCalculator;
use App\Module\Order\Application\Workflow\StripeCheckoutSessionSyncService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Shared\Application\UnitOfWork;

final readonly class RentalExtensionPaymentReconciliationService
{
    private const PRODUCT_PENDING_PAYMENT_TTL = 'PT5M';

    public function __construct(
        private OrderItemRepositoryPort $orderItems,
        private OrderRepositoryPort $orders,
        private OrderCheckoutSessionRepositoryPort $checkoutSessions,
        private UnitOfWork $persistence,
        private StripeCheckoutSessionSyncService $checkoutSync,
        private StripeCheckoutSessionExpirer $checkoutExpirer,
    ) {
    }

    /**
     * @param list<OrderItem> $items
     */
    public function reconcileCollection(array $items): void
    {
        foreach ($items as $item) {
            $this->reconcilePendingExtension($item);
        }
    }

    public function applyPaidExtensionOrder(Order $order): void
    {
        foreach ($order->getItems() as $extensionLine) {
            $sourceId = $extensionLine->getRentalOriginOrderItemId();
            if (null === $sourceId) {
                continue;
            }

            $rental = $this->orderItems->findById($sourceId);
            if (!$rental instanceof OrderItem || 'pending_payment' !== $rental->getRentalRequestStatus()) {
                continue;
            }

            $requestedEndDate = $rental->getRentalRequestedEndDate();
            $startDate = $rental->getRentalStartDate();
            if (null === $requestedEndDate || null === $startDate) {
                continue;
            }

            $coveredMonths = RentalPeriodCalculator::findMinimumMonthsCoveringEndDate($startDate, $requestedEndDate);
            if (null === $coveredMonths) {
                continue;
            }

            $rental->applyApprovedRentalExtension($requestedEndDate, $coveredMonths);
            $this->persistence->persist($rental);
        }

        $this->persistence->flush();
    }

    public function latestCheckoutSessionForOrder(int $orderId): ?OrderCheckoutSession
    {
        return $this->checkoutSessions->findRecentByOrderId($orderId, 1)[0] ?? null;
    }

    public function reload(OrderItem $item): OrderItem
    {
        return $this->orderItems->findById((int) $item->getId()) ?? $item;
    }

    public function reconcilePendingExtension(OrderItem $item, ?\DateTimeImmutable $now = null): ?OrderCheckoutSession
    {
        if ('pending_payment' !== $item->getRentalRequestStatus()) {
            return null;
        }

        $extensionOrderId = $item->getRentalExtensionOrderId();
        if (null === $extensionOrderId) {
            return null;
        }

        $now ??= new \DateTimeImmutable();
        $checkout = $this->latestCheckoutSessionForOrder($extensionOrderId);
        $order = $this->orders->find($extensionOrderId);
        if (!$order instanceof Order) {
            return null;
        }

        if ($checkout instanceof OrderCheckoutSession && OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus() && $this->isExpiredByProductPolicy($item, $checkout, $now)) {
            $this->cancelPendingExtension($item, $order, $checkout, true);

            return null;
        }

        if ($checkout instanceof OrderCheckoutSession && OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus()) {
            $this->checkoutSync->syncPayment($checkout);
            $checkout = $this->latestCheckoutSessionForOrder($extensionOrderId);
        }

        if ($checkout instanceof OrderCheckoutSession && OrderCheckoutSession::STATUS_PAID === $checkout->getStatus()) {
            if (Order::STATUS_PENDING === $order->getStatus()) {
                $order->setStatus(Order::STATUS_CONFIRMED);
                $this->persistence->persist($order);
                $this->persistence->flush();
            }
            $this->applyPaidExtensionOrder($order);

            return null;
        }

        if ($checkout instanceof OrderCheckoutSession && \in_array($checkout->getStatus(), [OrderCheckoutSession::STATUS_FAILED, OrderCheckoutSession::STATUS_EXPIRED], true)) {
            $this->cancelPendingExtension($item, $order, $checkout, false);

            return null;
        }

        return $checkout instanceof OrderCheckoutSession && OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus()
            ? $checkout
            : null;
    }

    public function cancelPendingExtensionAttempt(OrderItem $item, bool $expireStripeSession = true): void
    {
        if ('pending_payment' !== $item->getRentalRequestStatus()) {
            return;
        }

        $extensionOrderId = $item->getRentalExtensionOrderId();
        if (null === $extensionOrderId) {
            $item->clearRentalRequest();
            $this->persistence->persist($item);
            $this->persistence->flush();

            return;
        }

        $order = $this->orders->find($extensionOrderId);
        if (!$order instanceof Order) {
            $item->clearRentalRequest();
            $this->persistence->persist($item);
            $this->persistence->flush();

            return;
        }

        $this->cancelPendingExtension($item, $order, $this->latestCheckoutSessionForOrder($extensionOrderId), $expireStripeSession);
    }

    private function cancelPendingExtension(
        OrderItem $item,
        Order $order,
        ?OrderCheckoutSession $checkout,
        bool $expireStripeSession,
    ): void {
        if (
            $expireStripeSession
            && $checkout instanceof OrderCheckoutSession
            && OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus()
        ) {
            $this->checkoutExpirer->expire($checkout);
            $checkout->markExpired('rental_extension_product_timeout');
            $this->persistence->persist($checkout);
        }

        if (Order::STATUS_PENDING === $order->getStatus()) {
            $order
                ->setStatus(Order::STATUS_CANCELLED)
                ->setInvoiceStatus(Order::INVOICE_STATUS_CANCELLED);
            $this->persistence->persist($order);
        }

        $item->clearRentalRequest();
        $this->persistence->persist($item);
        $this->persistence->flush();
    }

    private function isExpiredByProductPolicy(
        OrderItem $item,
        ?OrderCheckoutSession $checkout,
        \DateTimeImmutable $now,
    ): bool {
        $createdAt = $item->getRentalRequestCreatedAt() ?? $checkout?->getCreatedAt();
        if (!$createdAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $createdAt->add(new \DateInterval(self::PRODUCT_PENDING_PAYMENT_TTL)) <= $now;
    }
}
