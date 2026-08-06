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
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.product = :product')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsForUserAndProduct(User $user, Product $product): bool
    {
        return (bool) $this->createQueryBuilder('f')
            ->select('1')
            ->andWhere('f.user = :user')
            ->andWhere('f.product = :product')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Favorite>
     */
    public function findFavoritesForUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('f')
            ->addSelect('p', 'c')
            ->join('f.product', 'p')
            ->join('p.category', 'c')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countFavoritesForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
