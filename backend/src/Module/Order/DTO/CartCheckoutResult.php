<?php

declare(strict_types=1);

namespace App\Module\Order\DTO;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderCheckoutSession;

final readonly class CartCheckoutResult
{
    private function __construct(
        public ?Order $order,
        public ?OrderCheckoutSession $checkout,
    ) {
    }

    public static function existingOrder(Order $order): self
    {
        return new self($order, null);
    }

    public static function redirect(OrderCheckoutSession $checkout): self
    {
        return new self(null, $checkout);
    }
}
