<?php

declare(strict_types=1);

namespace App\Module\User\Repository;

use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);

        if ($flush) {
            $entityManager->flush();
        }
    }

    public function existsByEmail(string $email): bool
    {
        return $this->createQueryBuilder('u')
            ->select('1')
            ->andWhere('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    public function existsByEmailExcludingUser(string $email, int $userId): bool
    {
        return $this->createQueryBuilder('u')
            ->select('1')
            ->andWhere('LOWER(u.email) = LOWER(:email)')
            ->andWhere('u.id != :userId')
            ->setParameter('email', $email)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    public function remove(User $user, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($user);

        if ($flush) {
            $entityManager->flush();
        }
    }

    public function findOneByVerificationToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.verificationToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
