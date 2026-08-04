<?php

declare(strict_types=1);

namespace App\Module\Outbox\Infrastructure\Repository;

use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\Outbox\Application\OutboxMetrics;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<OutboxEvent> */
final class OutboxEventRepository extends ServiceEntityRepository implements OutboxEventStore
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutboxEvent::class);
    }

    /** @return list<OutboxEvent> */
    public function findDueForUpdate(int $limit): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('event')
            ->andWhere('event.status IN (:statuses)')
            ->andWhere('event.availableAt <= :now')
            ->setParameter('statuses', [OutboxEvent::STATUS_PENDING, OutboxEvent::STATUS_FAILED])
            ->setParameter('now', $now)
            ->orderBy('event.createdAt', 'ASC')
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getResult();
    }

    public function purgeFinalizedBefore(\DateTimeImmutable $threshold): int
    {
        return (int) $this->createQueryBuilder('event')
            ->delete()
            ->andWhere('event.status IN (:statuses)')
            ->andWhere('event.createdAt < :threshold')
            ->setParameter('statuses', [OutboxEvent::STATUS_PROCESSED, OutboxEvent::STATUS_DEAD])
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    public function metricsSnapshot(\DateTimeImmutable $staleProcessingThreshold): OutboxMetrics
    {
        $now = new \DateTimeImmutable();
        $oldestPendingCreatedAt = $this->createQueryBuilder('event')
            ->select('MIN(event.createdAt)')
            ->andWhere('event.status IN (:statuses)')
            ->setParameter('statuses', [OutboxEvent::STATUS_PENDING, OutboxEvent::STATUS_FAILED])
            ->getQuery()
            ->getSingleScalarResult();

        return new OutboxMetrics(
            $this->countByStatuses([OutboxEvent::STATUS_PENDING, OutboxEvent::STATUS_FAILED]),
            \is_string($oldestPendingCreatedAt) && '' !== $oldestPendingCreatedAt
                ? max(0, $now->getTimestamp() - (new \DateTimeImmutable($oldestPendingCreatedAt))->getTimestamp())
                : null,
            $this->countByStatuses([OutboxEvent::STATUS_FAILED]),
            (int) $this->createQueryBuilder('event')
                ->select('COUNT(event.id)')
                ->andWhere('event.status = :status')
                ->andWhere('event.availableAt < :threshold')
                ->setParameter('status', OutboxEvent::STATUS_PROCESSING)
                ->setParameter('threshold', $staleProcessingThreshold)
                ->getQuery()
                ->getSingleScalarResult(),
            $this->countByStatuses([OutboxEvent::STATUS_DEAD]),
        );
    }

    /** @param list<string> $statuses */
    private function countByStatuses(array $statuses): int
    {
        return (int) $this->createQueryBuilder('event')
            ->select('COUNT(event.id)')
            ->andWhere('event.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
