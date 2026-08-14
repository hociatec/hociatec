<?php

declare(strict_types=1);

namespace App\Module\Favorite\Infrastructure\Repository;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Favorite\Application\Port\FavoriteRepositoryPort;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository implements FavoriteRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findOneByUserAndProduct(User $user, Product $product): ?Favorite
    {
        return $this->findOneByUserAndTarget($user, Favorite::CATEGORY_PRODUCT, $product->getId() ?? 0);
    }

    public function findOneByUserAndTarget(User $user, string $category, int $targetId): ?Favorite
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.category = :category')
            ->andWhere('f.targetId = :targetId')
            ->setParameter('user', $user)
            ->setParameter('category', Favorite::normalizeCategory($category))
            ->setParameter('targetId', max(1, $targetId))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsForUserAndProduct(User $user, Product $product): bool
    {
        return $this->existsForUserAndTarget($user, Favorite::CATEGORY_PRODUCT, $product->getId() ?? 0);
    }

    public function existsForUserAndTarget(User $user, string $category, int $targetId): bool
    {
        return (bool) $this->createQueryBuilder('f')
            ->select('1')
            ->andWhere('f.user = :user')
            ->andWhere('f.category = :category')
            ->andWhere('f.targetId = :targetId')
            ->setParameter('user', $user)
            ->setParameter('category', Favorite::normalizeCategory($category))
            ->setParameter('targetId', max(1, $targetId))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Favorite>
     */
    public function findFavoritesForUser(User $user, ?string $category = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)));

        if (null !== $category) {
            $qb
                ->andWhere('f.category = :category')
                ->setParameter('category', Favorite::normalizeCategory($category));
        }

        return $qb->getQuery()->getResult();
    }

    public function countFavoritesForUser(User $user, ?string $category = null): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user);

        if (null !== $category) {
            $qb
                ->andWhere('f.category = :category')
                ->setParameter('category', Favorite::normalizeCategory($category));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
