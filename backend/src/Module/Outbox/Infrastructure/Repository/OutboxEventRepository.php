<?php

declare(strict_types=1);

namespace App\Module\Outbox\Infrastructure\Repository;

use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\Outbox\Application\OutboxMetrics;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
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
        $limit = max(1, min(100, $limit));

        if ($this->supportsSkipLocked()) {
            $ids = $this->getEntityManager()->getConnection()->executeQuery(
                <<<'SQL'
                    SELECT id
                    FROM outbox_events
                    WHERE status IN (:statuses)
                      AND available_at <= :now
                    ORDER BY created_at ASC
                    LIMIT :limit
                    FOR UPDATE SKIP LOCKED
                    SQL,
                [
                    'statuses' => [OutboxEvent::STATUS_PENDING, OutboxEvent::STATUS_FAILED],
                    'now' => $now->format('Y-m-d H:i:s'),
                    'limit' => $limit,
                ],
                [
                    'statuses' => ArrayParameterType::STRING,
                    'now' => ParameterType::STRING,
                    'limit' => ParameterType::INTEGER,
                ],
            )->fetchFirstColumn();

            if ([] === $ids) {
                return [];
            }

            $events = $this->createQueryBuilder('event')
                ->andWhere('event.id IN (:ids)')
                ->setParameter('ids', array_map('intval', $ids))
                ->getQuery()
                ->getResult();

            $byId = [];
            foreach ($events as $event) {
                $byId[$event->getId()] = $event;
            }

            return array_values(array_filter(array_map(static fn (mixed $id): ?OutboxEvent => $byId[(int) $id] ?? null, $ids)));
        }

        return $this->createQueryBuilder('event')
            ->andWhere('event.status IN (:statuses)')
            ->andWhere('event.availableAt <= :now')
            ->setParameter('statuses', [OutboxEvent::STATUS_PENDING, OutboxEvent::STATUS_FAILED])
            ->setParameter('now', $now)
            ->orderBy('event.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
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

    public function recoverStaleProcessing(\DateTimeImmutable $threshold): int
    {
        return (int) $this->createQueryBuilder('event')
            ->update()
            ->set('event.status', ':failed')
            ->set('event.availableAt', ':now')
            ->set('event.lastError', ':lastError')
            ->andWhere('event.status = :processing')
            ->andWhere('event.availableAt < :threshold')
            ->setParameter('failed', OutboxEvent::STATUS_FAILED)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('lastError', 'Recovered after stale processing timeout.')
            ->setParameter('processing', OutboxEvent::STATUS_PROCESSING)
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

    private function supportsSkipLocked(): bool
    {
        return \in_array($this->getEntityManager()->getConnection()->getDatabasePlatform()->getName(), ['mysql', 'postgresql'], true);
    }
}
