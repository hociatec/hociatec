<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Repository;

use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findByUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.client = :u')
            ->setParameter('u', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.client = :u')
            ->setParameter('u', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array{search?:string,status?:string,type?:string,from?:string,to?:string,sort?:string} $filters
     *
     * @return list<AuditRequest>
     */
    public function findForAdminList(array $filters, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createAdminListQueryBuilder($filters)
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)));

        [$field, $direction] = $this->adminSort($filters['sort'] ?? 'date_desc');
        $qb->orderBy($field, $direction);

        return $qb->getQuery()->getResult();
    }

    /** @param array{search?:string,status?:string,type?:string,from?:string,to?:string,sort?:string} $filters */
    public function countForAdminList(array $filters): int
    {
        return (int) $this->createAdminListQueryBuilder($filters)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @param array{search?:string,status?:string,type?:string,from?:string,to?:string,sort?:string} $filters */
    private function createAdminListQueryBuilder(array $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        $search = trim((string) ($filters['search'] ?? ''));
        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(a.number) LIKE :search OR LOWER(a.targetUrl) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, [
            AuditRequest::STATUS_NEW,
            AuditRequest::STATUS_IN_PROGRESS,
            AuditRequest::STATUS_REVIEW,
            AuditRequest::STATUS_DONE,
        ], true)) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        $type = AuditType::tryFrom((string) ($filters['type'] ?? ''));
        if (null !== $type) {
            $qb->andWhere('a.type = :type')->setParameter('type', $type);
        }

        if (!empty($filters['from'])) {
            $qb->andWhere('a.createdAt >= :from')->setParameter('from', new \DateTimeImmutable($filters['from']));
        }

        if (!empty($filters['to'])) {
            $to = new \DateTimeImmutable($filters['to']);
            $qb->andWhere('a.createdAt <= :to')->setParameter('to', $to->setTime(23, 59, 59));
        }

        return $qb;
    }

    /** @return array{0:string,1:string} */
    private function adminSort(string $sort): array
    {
        return match ($sort) {
            'date_asc' => ['a.createdAt', 'ASC'],
            'number_asc' => ['a.number', 'ASC'],
            'number_desc' => ['a.number', 'DESC'],
            'status_asc' => ['a.status', 'ASC'],
            'status_desc' => ['a.status', 'DESC'],
            default => ['a.createdAt', 'DESC'],
        };
    }
}
