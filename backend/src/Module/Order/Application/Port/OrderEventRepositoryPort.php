<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;

interface OrderEventRepositoryPort
{
    /** @return list<OrderEvent> */
    public function findByOrder(Order $order, string $direction = 'DESC'): array;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<OrderEvent>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /**
     * @param list<Order> $orders
     *
     * @return array<int, list<OrderEvent>>
     */
    public function findIssueEventsGroupedByOrders(array $orders): array;
}
