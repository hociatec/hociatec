<?php

declare(strict_types=1);

namespace App\Module\Admin\Dashboard\Provider;

use App\Module\Order\Entity\OrderEvent;
use App\Module\Order\Repository\OrderEventRepository;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;

final readonly class DashboardActivityProvider
{
    public function __construct(
        private OrderRepository $orders,
        private OrderEventRepository $events,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentOrders(): array
    {
        return array_map(
            static fn ($order): array => OrderFormatter::formatOrder($order),
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
