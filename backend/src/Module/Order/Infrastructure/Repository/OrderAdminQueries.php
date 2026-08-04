<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\Order;

trait OrderAdminQueries
{
    /** @return list<Order> */
    public function findRecentForAdmin(int $limit = 8): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /** @return list<Order> */
    public function findPendingPaymentForAdmin(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.state.status = :status')
            ->setParameter('status', Order::STATUS_PENDING)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /** @return list<Order> */
    public function findFulfillmentQueue(int $limit = 30): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.state.status IN (:orderStatuses)')
            ->andWhere('o.delivery.status IN (:deliveryStatuses)')
            ->setParameter('orderStatuses', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
            ->setParameter('deliveryStatuses', [
                Order::DELIVERY_STATUS_PREPARING,
                Order::DELIVERY_STATUS_ISSUE,
            ])
            ->orderBy('o.createdAt', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}
