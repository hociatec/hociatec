<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Workflow;

use App\Module\Order\Domain\Entity\Order;

final readonly class OrderStatusWorkflow
{
    /**
     * @var array<string, array<string, string>>
     */
    private const TRANSITIONS = [
        Order::STATUS_PENDING => [
            Order::STATUS_CONFIRMED => 'confirm',
            Order::STATUS_CANCELLED => 'cancel',
        ],
        Order::STATUS_CONFIRMED => [
            Order::STATUS_DELIVERED => 'deliver',
        ],
    ];

    public function transitionFor(string $currentStatus, string $targetStatus): ?string
    {
        return self::TRANSITIONS[$currentStatus][$targetStatus] ?? null;
    }

    /** @return list<string> */
    public function statuses(): array
    {
        return [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];
    }

    /** @return list<string> */
    public function nextStatuses(string $status): array
    {
        return match ($status) {
            Order::STATUS_PENDING => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
            Order::STATUS_CONFIRMED => [Order::STATUS_DELIVERED],
            default => [],
        };
    }

    public function canTransitionTo(string $currentStatus, string $targetStatus): bool
    {
        return in_array($targetStatus, $this->nextStatuses($currentStatus), true);
    }
}
