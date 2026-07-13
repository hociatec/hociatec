<?php

declare(strict_types=1);

namespace App\Module\Order\Message;

final class OrderCreatedMessage
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly int $userId,
    ) {}
}

