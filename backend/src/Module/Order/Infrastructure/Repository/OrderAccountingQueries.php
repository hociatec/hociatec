<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

trait OrderAccountingQueries
{
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
            ->andWhere('o.invoice.invoicedAt BETWEEN :from AND :to')
            ->andWhere('o.invoice.number IS NOT NULL')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
