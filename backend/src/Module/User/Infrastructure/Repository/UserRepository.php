<?php

declare(strict_types=1);

namespace App\Module\User\Infrastructure\Repository;

use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserRepositoryPort
{
    use UserAdminCustomerQueries;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?User
    {
        $user = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $user instanceof User ? $user : null;
    }

    public function save(User $user): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
    }

    public function findForUpdate(int $id): ?User
    {
        $user = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $user instanceof User ? $user : null;
    }

    public function existsByEmail(string $email): bool
    {
        return null !== $this->createQueryBuilder('u')
            ->select('1')
            ->andWhere('LOWER(u.identity.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmailInsensitive(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.identity.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsByEmailExcludingUser(string $email, int $userId): bool
    {
        return null !== $this->createQueryBuilder('u')
            ->select('1')
            ->andWhere('LOWER(u.identity.email) = LOWER(:email)')
            ->andWhere('u.id != :userId')
            ->setParameter('email', $email)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function remove(User $user): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($user);
    }

    public function findOneByVerificationTokens(string $hashedToken, string $legacyToken): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.security.verificationToken = :hashedToken OR u.security.verificationToken = :legacyToken')
            ->setParameter('hashedToken', $hashedToken)
            ->setParameter('legacyToken', $legacyToken)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByPasswordResetToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.security.passwordResetToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<User>
     */
    public function findAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.security.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<User>
     */
    public function findNewsEmailSubscribers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.security.isVerified = :verified')
            ->andWhere('u.communication.communicationPreferences LIKE :preference')
            ->setParameter('verified', true)
            ->setParameter('preference', '%news_email%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<User>
     */
    public function findLoyaltyCustomers(string $search, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.administration.loyaltyPointsBalance', 'DESC')
            ->addOrderBy('u.createdAt', 'DESC')
            ->setMaxResults(max(1, min(100, $limit)))
            ->setFirstResult(max(0, $offset));

        $normalizedSearch = trim($search);
        if ('' !== $normalizedSearch) {
            $qb
                ->andWhere('LOWER(u.identity.email) LIKE LOWER(:search) OR LOWER(u.identity.firstName) LIKE LOWER(:search) OR LOWER(u.identity.lastName) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$normalizedSearch.'%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countLoyaltyCustomers(string $search): int
    {
        $qb = $this->createQueryBuilder('u')->select('COUNT(u.id)');

        $normalizedSearch = trim($search);
        if ('' !== $normalizedSearch) {
            $qb
                ->andWhere('LOWER(u.identity.email) LIKE LOWER(:search) OR LOWER(u.identity.firstName) LIKE LOWER(:search) OR LOWER(u.identity.lastName) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$normalizedSearch.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
