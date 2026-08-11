<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Repository;

use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use App\Shared\Infrastructure\Persistence\LikeSearchHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BetaTesterProfile>
 */
final class BetaTesterProfileRepository extends ServiceEntityRepository implements BetaTesterProfileRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetaTesterProfile::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BetaTesterProfile
    {
        $profile = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $profile instanceof BetaTesterProfile ? $profile : null;
    }

    public function findOneByUser(User $user): ?BetaTesterProfile
    {
        $result = $this->findOneBy(['user' => $user]);

        return $result instanceof BetaTesterProfile ? $result : null;
    }

    /** @return list<BetaTesterProfile> */
    public function findForAdminList(string $search = '', string $status = '', string $accessibility = '', int $limit = 20, int $offset = 0): array
    {
        return $this->createAdminListQuery($search, $status, $accessibility)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countForAdminList(string $search = '', string $status = '', string $accessibility = ''): int
    {
        return (int) $this->createAdminListQuery($search, $status, $accessibility)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createAdminListQuery(string $search, string $status, string $accessibility): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->addSelect('u');

        $searchPattern = LikeSearchHelper::containsPattern($search, true);
        if (null !== $searchPattern) {
            $qb->andWhere('LOWER(u.identity.firstName) LIKE :search OR LOWER(u.identity.lastName) LIKE :search OR LOWER(u.identity.email) LIKE :search')
                ->setParameter('search', $searchPattern);
        }

        if ('' !== trim($status)) {
            $qb->andWhere('p.status = :status')->setParameter('status', trim($status));
        }

        if ('' !== trim($accessibility)) {
            $qb->andWhere('p.accessibilityNeed = :accessibility')->setParameter('accessibility', trim($accessibility));
        }

        return $qb;
    }
}
