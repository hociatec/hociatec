<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;

final readonly class StripeCheckoutService
{
    public function __construct(
        private CartHostedCheckoutCreator $cartCheckouts,
        private OrderHostedCheckoutCreator $orderCheckouts,
    ) {
    }

    public function createHostedCheckout(User $user, CartSession $cart, ShippingAddress $address): OrderCheckoutSession
    {
        return $this->cartCheckouts->create($user, $cart, $address);
    }

    public function createHostedCheckoutForOrder(User $user, Order $order, ShippingAddress $address): OrderCheckoutSession
    {
        return $this->orderCheckouts->create($user, $order, $address);
    }
}
