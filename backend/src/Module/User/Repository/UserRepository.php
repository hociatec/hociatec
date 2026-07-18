<?php

declare(strict_types=1);

namespace App\Module\User\Repository;

use App\Module\Order\Entity\Order;
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

    public function findOneByPasswordResetToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.passwordResetToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
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
    public function findAdminCustomerRows(?string $search = null, string $sort = 'recent_order', int $limit = 100): array
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
        if ($normalizedSearch !== '') {
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
                ->setParameter('search', '%' . $normalizedSearch . '%');
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
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) ($row['id'] ?? 0),
                'email' => (string) ($row['email'] ?? ''),
                'firstName' => (string) ($row['firstName'] ?? ''),
                'lastName' => (string) ($row['lastName'] ?? ''),
                'phoneNumber' => (string) ($row['phoneNumber'] ?? ''),
                'isVerified' => (bool) ($row['isVerified'] ?? false),
                'adminTags' => array_values(array_filter(
                    array_map(static fn (mixed $tag): string => trim((string) $tag), is_array($row['adminTags'] ?? null) ? $row['adminTags'] : []),
                    static fn (string $tag): bool => $tag !== '',
                )),
                'createdAt' => $row['createdAt'] instanceof \DateTimeInterface ? $row['createdAt']->format(DATE_ATOM) : (string) ($row['createdAt'] ?? ''),
                'ordersCount' => (int) ($row['ordersCount'] ?? 0),
                'totalSpentCents' => (int) ($row['totalSpentCents'] ?? 0),
                'lastOrderAt' => $row['lastOrderAt'] instanceof \DateTimeInterface ? $row['lastOrderAt']->format(DATE_ATOM) : ($row['lastOrderAt'] !== null ? (string) $row['lastOrderAt'] : null),
            ],
            $rows,
        );
    }
}
