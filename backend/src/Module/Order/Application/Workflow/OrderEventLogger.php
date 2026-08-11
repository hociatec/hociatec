<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final class OrderEventLogger
{
    public function __construct(private readonly UnitOfWork $unitOfWork)
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

        $this->unitOfWork->persist($event);
        $this->unitOfWork->flush();
    }
}
