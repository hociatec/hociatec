<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Security;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\User;

final readonly class OrderAccessPolicy
{
    public function canView(User $user, Order $order): bool
    {
        return $this->isSameUser($user, $order->getUser());
    }

    public function canCancel(User $user, Order $order): bool
    {
        return $this->canView($user, $order)
            && \in_array($order->getStatus(), [Order::STATUS_PENDING, Order::STATUS_CONFIRMED], true)
            && Order::DELIVERY_STATUS_PREPARING === $order->getDeliveryStatus()
            && Order::INVOICE_STATUS_ISSUED === $order->getInvoiceStatus()
            && $order->getCreatedAt() >= new \DateTimeImmutable('-14 days');
    }

    public function canCheckout(User $user, Order $order): bool
    {
        return $this->canView($user, $order)
            && Order::STATUS_PENDING === $order->getStatus();
    }

    public function canDownloadInvoice(User $user, Order $order): bool
    {
        return $this->canView($user, $order)
            && Order::INVOICE_STATUS_ISSUED === $order->getInvoiceStatus()
            && Order::STATUS_CANCELLED !== $order->getStatus();
    }

    public function canViewCheckoutSession(User $user, OrderCheckoutSession $checkout): bool
    {
        return $this->isSameUser($user, $checkout->getUser());
    }

    private function isSameUser(User $user, User $owner): bool
    {
        $userId = $user->getId();
        $ownerId = $owner->getId();

        if (null !== $userId && null !== $ownerId) {
            return $ownerId === $userId;
        }

        return $owner === $user;
    }
}
