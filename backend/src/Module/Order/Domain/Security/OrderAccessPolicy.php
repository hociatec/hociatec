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
        return $order->getUser()->getId() === $user->getId();
    }

    public function canCancel(User $user, Order $order): bool
    {
        return $this->canView($user, $order);
    }

    public function canCheckout(User $user, Order $order): bool
    {
        return $this->canView($user, $order);
    }

    public function canDownloadInvoice(User $user, Order $order): bool
    {
        return $this->canView($user, $order);
    }

    public function canViewCheckoutSession(User $user, OrderCheckoutSession $checkout): bool
    {
        return $checkout->getUser()->getId() === $user->getId();
    }
}
