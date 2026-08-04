<?php

declare(strict_types=1);

namespace App\Module\User\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
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
            ->andWhere('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmailInsensitive(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsByEmailExcludingUser(string $email, int $userId): bool
    {
        return null !== $this->createQueryBuilder('u')
            ->select('1')
            ->andWhere('LOWER(u.email) = LOWER(:email)')
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
            ->andWhere('u.verificationToken = :hashedToken OR u.verificationToken = :legacyToken')
            ->setParameter('hashedToken', $hashedToken)
            ->setParameter('legacyToken', $legacyToken)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByPasswordResetToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.passwordResetToken = :token')
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
            ->andWhere('u.roles LIKE :role')
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
            ->andWhere('u.isVerified = :verified')
            ->andWhere('u.communicationPreferences LIKE :preference')
            ->setParameter('verified', true)
            ->setParameter('preference', '%news_email%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{
     *   id:int,
     *   email:string,
     *   firstName:string,
     *   lastName:string,
     *   phoneNumber:string,
     *   isVerified:bool,
     *   adminTags:list<string>,
     *   createdAt:string,
     *   ordersCount:int,
     *   totalSpentCents:int,
     *   lastOrderAt:?string
     * }>
     */
    public function findAdminCustomerRows(?string $search = null, string $sort = 'recent_order', int $limit = 100, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select(
                'u.id AS id',
                'u.email AS email',
                'u.firstName AS firstName',
                'u.lastName AS lastName',
                'u.phoneNumber AS phoneNumber',
                'u.isVerified AS isVerified',
                'u.adminTags AS adminTags',
                'u.createdAt AS createdAt',
                'COUNT(o.id) AS ordersCount',
                'COALESCE(SUM(o.totalPriceCents), 0) AS totalSpentCents',
                'MAX(o.createdAt) AS lastOrderAt'
            )
            ->leftJoin(Order::class, 'o', 'WITH', 'o.user = u')
            ->groupBy('u.id');

        $normalizedSearch = trim((string) $search);
        if ('' !== $normalizedSearch) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'LOWER(u.email) LIKE LOWER(:search)',
                        'LOWER(u.firstName) LIKE LOWER(:search)',
                        'LOWER(u.lastName) LIKE LOWER(:search)',
                        'LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE LOWER(:search)',
                        'LOWER(u.phoneNumber) LIKE LOWER(:search)',
                        'LOWER(o.number) LIKE LOWER(:search)'
                    )
                )
                ->setParameter('search', '%'.$normalizedSearch.'%');
        }

        match ($sort) {
            'highest_spent' => $qb->orderBy('totalSpentCents', 'DESC')->addOrderBy('lastOrderAt', 'DESC'),
            'most_orders' => $qb->orderBy('ordersCount', 'DESC')->addOrderBy('lastOrderAt', 'DESC'),
            'newest_account' => $qb->orderBy('u.createdAt', 'DESC'),
            'name_asc' => $qb->orderBy('u.lastName', 'ASC')->addOrderBy('u.firstName', 'ASC'),
            default => $qb->orderBy('lastOrderAt', 'DESC')->addOrderBy('u.createdAt', 'DESC'),
        };

        $rows = $qb
            ->setMaxResults(max(1, min(200, $limit)))
            ->setFirstResult(max(0, $offset))
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(
            static fn (array $row): array => [
                'id' => (int) ($row['id'] ?? 0),
                'email' => (string) ($row['email'] ?? ''),
                'firstName' => (string) ($row['firstName'] ?? ''),
                'lastName' => (string) ($row['lastName'] ?? ''),
                'phoneNumber' => (string) ($row['phoneNumber'] ?? ''),
                'isVerified' => (bool) ($row['isVerified'] ?? false),
                'adminTags' => array_values(array_filter(
                    array_map(static fn (mixed $tag): string => trim((string) $tag), is_array($row['adminTags'] ?? null) ? $row['adminTags'] : []),
                    static fn (string $tag): bool => '' !== $tag,
                )),
                'createdAt' => $row['createdAt'] instanceof \DateTimeInterface ? $row['createdAt']->format(DATE_ATOM) : (string) ($row['createdAt'] ?? ''),
                'ordersCount' => (int) ($row['ordersCount'] ?? 0),
                'totalSpentCents' => (int) ($row['totalSpentCents'] ?? 0),
                'lastOrderAt' => $row['lastOrderAt'] instanceof \DateTimeInterface ? $row['lastOrderAt']->format(DATE_ATOM) : (null !== $row['lastOrderAt'] ? (string) $row['lastOrderAt'] : null),
            ],
            $rows,
        ));
    }

    public function countAdminCustomerRows(?string $search = null): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->leftJoin(Order::class, 'o', 'WITH', 'o.user = u');

        $normalizedSearch = trim((string) $search);
        if ('' !== $normalizedSearch) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'LOWER(u.email) LIKE LOWER(:search)',
                        'LOWER(u.firstName) LIKE LOWER(:search)',
                        'LOWER(u.lastName) LIKE LOWER(:search)',
                        'LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE LOWER(:search)',
                        'LOWER(u.phoneNumber) LIKE LOWER(:search)',
                        'LOWER(o.number) LIKE LOWER(:search)',
                    )
                )
                ->setParameter('search', '%'.$normalizedSearch.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
