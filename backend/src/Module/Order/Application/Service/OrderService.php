<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;

final readonly class OrderService
{
    public function __construct(
        private CartOrderCreator $cartOrders,
        private CheckoutSessionOrderCreator $checkoutOrders,
        private OrderPostCreationProcessor $postCreation,
    ) {
    }

    public function createFromCart(User $user, CartSession $cart): Order
    {
        throw new \InvalidArgumentException('Adresse de livraison requise. Utiliser createFromCartWithAddress().');
    }

    public function createFromCartWithAddress(User $user, CartSession $cart, ShippingAddress $address): Order
    {
        $order = $this->cartOrders->create($user, $cart, $address);
        $this->postCreation->process($order, $user, false);

        return $order;
    }

    public function createFromCheckoutSession(OrderCheckoutSession $checkout): Order
    {
        $order = $this->checkoutOrders->create($checkout);
        $this->postCreation->process($order, $checkout->getUser(), true);

        return $order;
    }
}
