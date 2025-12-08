<?php

declare(strict_types=1);

namespace App\Module\Audit\Repository;

use App\Module\Audit\Entity\AuditEvent;
use App\Module\Audit\Entity\AuditRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditEvent>
 */
class AuditEventRepository extends ServiceEntityRepository
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
            ->orderBy('e.createdAt', $order === 'ASC' ? 'ASC' : 'DESC')
            ->getQuery()
            ->getResult();
    }
}

