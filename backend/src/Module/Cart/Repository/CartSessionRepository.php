<?php

declare(strict_types=1);

namespace App\Module\Cart\Repository;

use App\Module\Cart\Entity\CartSession;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartSession>
 */
final class CartSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartSession::class);
    }

    public function findOneByToken(string $token): ?CartSession
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findOneByUser(User $user): ?CartSession
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findOneByUserId(int $userId): ?CartSession
    {
        return $this->createQueryBuilder('c')
            ->andWhere('IDENTITY(c.user) = :uid')
            ->setParameter('uid', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
