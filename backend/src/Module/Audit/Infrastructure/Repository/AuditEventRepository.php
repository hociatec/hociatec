<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Repository;

use App\Module\Audit\Application\Port\AuditEventRepositoryPort;

use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditEvent>
 */
class AuditEventRepository extends ServiceEntityRepository implements AuditEventRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditEvent::class);
    }

    /**
     * @return list<AuditEvent>
     */
    public function findByAudit(AuditRequest $audit, string $order = 'DESC'): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.audit = :a')
            ->setParameter('a', $audit)
            ->orderBy('e.createdAt', 'ASC' === $order ? 'ASC' : 'DESC')
            ->getQuery()
            ->getResult();
    }
}
