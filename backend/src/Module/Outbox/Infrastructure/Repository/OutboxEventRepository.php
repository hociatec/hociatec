<?php

declare(strict_types=1);

namespace App\Module\Outbox\Infrastructure\Repository;

use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<OutboxEvent> */
final class OutboxEventRepository extends ServiceEntityRepository implements OutboxEventStore
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutboxEvent::class);
    }

    /** @return list<OutboxEvent> */
    public function findDue(int $limit): array
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
            ->getResult();
    }
}
