<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\Order;

trait OrderAdminQueries
{
    private const ISSUE_EVENT_TYPES = [
        'email_failed',
        'invoice_generation_failed',
        'post_processing_failed',
    ];

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

    /** @return list<Order> */
    public function findForAdminList(?string $status, ?string $health, int $limit, int $offset): array
    {
        $qb = $this->createAdminListQuery($status, $health)
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var list<Order> $orders */
        $orders = $qb->getQuery()->getResult();

        return $orders;
    }

    public function countForAdminList(?string $status, ?string $health): int
    {
        return (int) $this->createAdminListQuery($status, $health)
            ->select('COUNT(DISTINCT o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createAdminListQuery(?string $status, ?string $health): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('o');

        if (null !== $status && '' !== $status && 'all' !== $status) {
            $qb->andWhere('o.state.status = :status')->setParameter('status', $status);
        }

        if ('issues' === $health) {
            $qb
                ->leftJoin('App\Module\Order\Domain\Entity\OrderEvent', 'e', 'WITH', 'e.order = o')
                ->andWhere(
                    $qb->expr()->orX(
                        'o.invoice.pdfPath IS NULL',
                        'o.invoice.xmlPath IS NULL',
                        'o.orderCreatedEmailSentAt IS NULL',
                        $qb->expr()->in('e.type', ':issueTypes'),
                    )
                )
                ->setParameter('issueTypes', self::ISSUE_EVENT_TYPES)
                ->groupBy('o.id');
        }

        return $qb;
    }
}
