<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\CheckoutStatus;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;

trait OrderCheckoutSessionDashboardQueries
{
    /** @return array<string, int> */
    public function getStatusCounts(): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.lifecycle.status AS status', 'COUNT(s.id) AS total')
            ->groupBy('s.lifecycle.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [
            OrderCheckoutSession::STATUS_OPEN => 0,
            OrderCheckoutSession::STATUS_PAID => 0,
            OrderCheckoutSession::STATUS_EXPIRED => 0,
            OrderCheckoutSession::STATUS_FAILED => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'] ?? '';
            if ($status instanceof CheckoutStatus) {
                $status = $status->value;
            }
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $counts;
    }

    public function countPaidWithoutOrder(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.lifecycle.status = :status')
            ->andWhere('s.lifecycle.orderId IS NULL')
            ->setParameter('status', OrderCheckoutSession::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<OrderCheckoutSession> */
    public function findRecentForDashboard(int $limit = 6): array
    {
        /** @var list<OrderCheckoutSession> $items */
        $items = $this->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<OrderCheckoutSession> */
    public function findAttentionItemsForDashboard(int $limit = 6): array
    {
        /** @var list<OrderCheckoutSession> $items */
        $items = $this->createQueryBuilder('s')
            ->andWhere('s.lifecycle.status IN (:statuses) OR (s.lifecycle.status = :paidStatus AND s.lifecycle.orderId IS NULL)')
            ->setParameter('statuses', [
                OrderCheckoutSession::STATUS_FAILED,
                OrderCheckoutSession::STATUS_EXPIRED,
            ])
            ->setParameter('paidStatus', OrderCheckoutSession::STATUS_PAID)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<OrderCheckoutSession> */
    public function findRecentOpen(int $limit = 20): array
    {
        /** @var list<OrderCheckoutSession> $items */
        $items = $this->createQueryBuilder('s')
            ->andWhere('s.lifecycle.status = :status')
            ->setParameter('status', OrderCheckoutSession::STATUS_OPEN)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }
}
