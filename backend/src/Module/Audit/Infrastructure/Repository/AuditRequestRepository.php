<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Repository;

use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;

use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditRequest>
 */
class AuditRequestRepository extends ServiceEntityRepository implements AuditRequestRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditRequest::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?AuditRequest
    {
        $audit = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $audit instanceof AuditRequest ? $audit : null;
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
