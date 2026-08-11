<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Application\Port\CategoryRepositoryPort;
use App\Module\Catalog\Domain\Entity\Category;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use App\Shared\Infrastructure\Persistence\LikeSearchHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository implements CategoryRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Category
    {
        $category = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $category instanceof Category ? $category : null;
    }

    /**
     * @return list<Category>
     */
    public function findAllVisibleOrdered(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('visible', true)
            ->orderBy('c.name', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countVisible(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('visible', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Category>
     */
    public function findAllForAdmin(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c');
        $this->applyAdminSearch($qb, $search);

        return $qb
            ->orderBy('c.name', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?string $search = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');
        $this->applyAdminSearch($qb, $search);

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneVisibleBySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('slug', $slug)
            ->setParameter('visible', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsWithSlug(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('1')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb
                ->andWhere('c.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function existsWithName(string $name, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('1')
            ->andWhere('LOWER(c.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb
                ->andWhere('c.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    private function applyAdminSearch(\Doctrine\ORM\QueryBuilder $qb, ?string $search): void
    {
        $pattern = LikeSearchHelper::containsPattern($search, true);
        if (null === $pattern) {
            return;
        }

        $qb
            ->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.slug) LIKE :search')
            ->setParameter('search', $pattern);
    }
}
