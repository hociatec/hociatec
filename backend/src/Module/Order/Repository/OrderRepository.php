<?php

declare(strict_types=1);

namespace App\Module\Order\Repository;

use App\Module\Order\Entity\Order;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function countForYear(int $year): int
    {
        $from = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $to = new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));

        $qb = $this->createQueryBuilder('o');
        $qb->select('COUNT(o.id)')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countInvoicedForYear(int $year): int
    {
        $from = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $to = new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));

        $qb = $this->createQueryBuilder('o');
        $qb->select('COUNT(o.id)')
            ->andWhere('o.invoicedAt BETWEEN :from AND :to')
            ->andWhere('o.invoiceNumber IS NOT NULL')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Order>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Order>
     */
    public function findRecentForAdmin(int $limit = 8): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Order>
     */
    public function findPendingPaymentForAdmin(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', Order::STATUS_PENDING)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Order>
     */
    public function findFulfillmentQueue(int $limit = 30): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status IN (:orderStatuses)')
            ->andWhere('o.deliveryStatus IN (:deliveryStatuses)')
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

    /**
     * @return array{count:int,totalCents:int}
     */
    public function getSummaryBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $result = $this->createQueryBuilder('o')
            ->select('COUNT(o.id) AS ordersCount', 'COALESCE(SUM(o.totalPriceCents), 0) AS totalCents')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleResult();

        return [
            'count' => (int) ($result['ordersCount'] ?? 0),
            'totalCents' => (int) ($result['totalCents'] ?? 0),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function getStatusCounts(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status', 'COUNT(o.id) AS count')
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [
            Order::STATUS_PENDING => 0,
            Order::STATUS_CONFIRMED => 0,
            Order::STATUS_DELIVERED => 0,
            Order::STATUS_CANCELLED => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if ('' === $status) {
                continue;
            }

            $counts[$status] = (int) ($row['count'] ?? 0);
        }

        return $counts;
    }

    public function countWithOperationalIssues(): int
    {
        $qb = $this->createQueryBuilder('o');

        return (int) $qb
            ->select('COUNT(DISTINCT o.id)')
            ->leftJoin('App\Module\Order\Entity\OrderEvent', 'e', 'WITH', 'e.order = o')
            ->andWhere(
                $qb->expr()->orX(
                    'o.invoicePdfPath IS NULL',
                    'o.invoiceXmlPath IS NULL',
                    'o.orderCreatedEmailSentAt IS NULL',
                    $qb->expr()->in('e.type', ':issueTypes'),
                )
            )
            ->setParameter('issueTypes', [
                'email_failed',
                'invoice_generation_failed',
                'post_processing_failed',
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
