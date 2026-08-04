<?php

declare(strict_types=1);

namespace App\Module\Quote\Infrastructure\Repository;

use App\Module\Quote\Domain\Entity\Quote;
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

    /**
     * @return list<Quote>
     */
    public function findBySearch(?string $search, ?string $statusCode): array
    {
        $qb = $this->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC');

        if (null !== $search && '' !== $search) {
            $qb->andWhere('q.number LIKE :term OR q.customerName LIKE :term OR q.customerEmail LIKE :term')
                ->setParameter('term', '%'.$search.'%');
        }

        if (null !== $statusCode) {
            $statusCode = trim($statusCode);
            if ('' !== $statusCode && 'all' !== strtolower($statusCode)) {
                $qb->andWhere('q.status = :status')
                    ->setParameter('status', $statusCode);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Quote>
     */
    public function findAcceptedWaitingForConversion(int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.status = :status')
            ->andWhere('q.convertedOrder IS NULL')
            ->setParameter('status', Quote::STATUS_ACCEPTED)
            ->orderBy('q.updatedAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<string> $statusCodes
     *
     * @return list<Quote>
     */
    public function findRecentByStatuses(array $statusCodes, int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.status IN (:statuses)')
            ->setParameter('statuses', $statusCodes)
            ->orderBy('q.updatedAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Quote>
     */
    public function findRecentlyEmailed(int $limit = 6): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.createdEmailSentAt IS NOT NULL')
            ->orderBy('q.createdEmailSentAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}
