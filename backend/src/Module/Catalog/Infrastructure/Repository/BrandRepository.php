<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Application\Port\BrandRepositoryPort;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Brand>
 */
class BrandRepository extends ServiceEntityRepository implements BrandRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Brand
    {
        $brand = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $brand instanceof Brand ? $brand : null;
    }

    /**
     * @return list<Brand>
     */
    public function findAllForAdmin(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('b');
        $this->applySearch($qb, $search);

        return $qb
            ->orderBy('b.name', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?string $search = null): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)');
        $this->applySearch($qb, $search);

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function existsWithName(string $name, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('b')
            ->select('1')
            ->andWhere('LOWER(b.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb
                ->andWhere('b.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function findOneByName(string $name): ?Brand
    {
        return $this->createQueryBuilder('b')
            ->andWhere('LOWER(b.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function applySearch(\Doctrine\ORM\QueryBuilder $qb, ?string $search): void
    {
        $term = null === $search ? '' : trim(mb_strtolower($search));

        if ('' === $term) {
            return;
        }

        $qb
            ->andWhere('LOWER(b.name) LIKE :search')
            ->setParameter('search', sprintf('%%%s%%', $term));
    }
}
