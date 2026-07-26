<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderEvent;
use App\Module\User\Entity\User;

final class OrderEventLogger
{
    public function __construct(private readonly OrderEventPersistence $persistence)
    {
    }

    public function log(Order $order, ?User $actor, string $type, ?string $message = null): void
    {
        $event = new OrderEvent(
            $order,
            $type,
            $message,
            $actor?->getId(),
            $actor?->getFullName() ?? $actor?->getEmail(),
        );

        $this->persistence->save($event);
    }
}
