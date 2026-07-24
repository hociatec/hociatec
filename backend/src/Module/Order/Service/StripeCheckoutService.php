<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;

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
