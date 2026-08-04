<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\Order;

trait OrderOperationsMetricsQueries
{
    /** @return array{count:int,totalCents:int} */
    public function getSummaryBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $result = $this->createQueryBuilder('o')
            ->select('COUNT(o.id) AS ordersCount', 'COALESCE(SUM(o.payment.totalPriceCents), 0) AS totalCents')
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

    /** @return array<string,int> */
    public function getStatusCounts(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.state.status AS status', 'COUNT(o.id) AS count')
            ->groupBy('o.state.status')
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
            ->leftJoin('App\Module\Order\Domain\Entity\OrderEvent', 'e', 'WITH', 'e.order = o')
            ->andWhere(
                $qb->expr()->orX(
                    'o.invoice.pdfPath IS NULL',
                    'o.invoice.xmlPath IS NULL',
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
