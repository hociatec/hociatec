<?php

declare(strict_types=1);

namespace App\Module\Favorite\Repository;

use App\Module\Catalog\Entity\Product;
use App\Module\Favorite\Entity\Favorite;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
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
    public function findFavoritesForUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->addSelect('p', 'c')
            ->join('f.product', 'p')
            ->join('p.category', 'c')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
