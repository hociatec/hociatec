<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Message;

final class OrderStatusChangedMessage
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {
    }
}
