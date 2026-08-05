<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Dashboard\Provider;

use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\OrderEvent;

final readonly class DashboardActivityProvider
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OrderEventRepositoryPort $events,
        private OrderFormatter $orderFormatter,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentOrders(): array
    {
        return array_map(
            fn ($order): array => $this->orderFormatter->formatOrder($order),
            $this->orders->findRecentForAdmin(6),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentEvents(): array
    {
        return array_map(
            static fn (OrderEvent $event): array => [
                'id' => $event->getId(),
                'type' => $event->getType(),
                'message' => $event->getMessage(),
                'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
                'order' => [
                    'id' => $event->getOrder()->getId(),
                    'number' => $event->getOrder()->getNumber(),
                ],
                'actor' => [
                    'id' => $event->getActorUserId(),
                    'name' => $event->getActorName(),
                ],
            ],
            $this->events->findBy([], ['createdAt' => 'DESC'], 8),
        );
    }
}
