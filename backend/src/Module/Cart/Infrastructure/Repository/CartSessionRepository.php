<?php

declare(strict_types=1);

namespace App\Module\Cart\Infrastructure\Repository;

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
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
        return $this->findOneBy(['token' => $token]);
    }

    public function findForUpdate(int $id): ?CartSession
    {
        $cart = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $cart instanceof CartSession ? $cart : null;
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

    public function clearUnitOfWork(): void
    {
        $this->getEntityManager()->clear();
    }
}
