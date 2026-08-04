<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Repository;

use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditRequest>
 */
class AuditRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditRequest::class);
    }

    /**
     * @return list<AuditRequest>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.client = :u')
            ->setParameter('u', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
