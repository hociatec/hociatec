<?php

declare(strict_types=1);

namespace App\Module\Cart\Infrastructure\Repository;

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartSession>
 */
final class CartSessionRepository extends ServiceEntityRepository implements CartSessionRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartSession::class);
    }

    public function findOneByToken(string $token): ?CartSession
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.token = :token')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForUpdate(int $id): ?CartSession
    {
        $cart = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $cart instanceof CartSession ? $cart : null;
    }

    public function findOneByUser(User $user): ?CartSession
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByUserId(int $userId): ?CartSession
    {
        return $this->createQueryBuilder('c')
            ->andWhere('IDENTITY(c.user) = :uid')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('uid', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function clearUnitOfWork(): void
    {
        $this->getEntityManager()->clear();
    }
}
