<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderEvent>
 */
final class OrderEventRepository extends ServiceEntityRepository
{
    private const ISSUE_TYPES = [
        'email_failed',
        'invoice_generation_failed',
        'post_processing_failed',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderEvent::class);
    }

    /**
     * @return list<OrderEvent>
     */
    public function findByOrder(Order $order, string $direction = 'DESC'): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.order = :order')
            ->setParameter('order', $order)
            ->orderBy('e.createdAt', 'ASC' === $direction ? 'ASC' : 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<Order> $orders
     *
     * @return array<int, list<OrderEvent>>
     */
    public function findIssueEventsGroupedByOrders(array $orders): array
    {
        if ([] === $orders) {
            return [];
        }

        $events = $this->createQueryBuilder('e')
            ->andWhere('e.order IN (:orders)')
            ->andWhere('e.type IN (:types)')
            ->setParameter('orders', $orders)
            ->setParameter('types', self::ISSUE_TYPES)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];

        foreach ($events as $event) {
            $orderId = $event->getOrder()->getId();
            if (null === $orderId) {
                continue;
            }

            $grouped[$orderId] ??= [];
            $grouped[$orderId][] = $event;
        }

        return $grouped;
    }
}
