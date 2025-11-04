<?php

declare(strict_types=1);

namespace App\Module\Quote\Repository;

use App\Module\Quote\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function countForYear(int $year): int
    {
        $from = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $to = new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));

        $qb = $this->createQueryBuilder('q');
        $qb->select('COUNT(q.id)')
            ->andWhere('q.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findBySearch(?string $search, ?string $status): array
    {
        $qb = $this->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC');

        if ($search !== null && $search !== '') {
            $qb->andWhere('q.number LIKE :term OR q.customerName LIKE :term OR q.customerEmail LIKE :term')
                ->setParameter('term', '%'.$search.'%');
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            $qb->andWhere('q.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}

