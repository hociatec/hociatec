<?php

declare(strict_types=1);

namespace App\Module\User\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Persistence\LikeSearchHelper;
use Doctrine\ORM\QueryBuilder;

trait UserAdminCustomerQueries
{
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
                'u.identity.email AS email',
                'u.identity.firstName AS firstName',
                'u.identity.lastName AS lastName',
                'u.identity.phoneNumber AS phoneNumber',
                'u.security.isVerified AS isVerified',
                'u.administration.adminTags AS adminTags',
                'u.createdAt AS createdAt',
                'COUNT(o.id) AS ordersCount',
                'COALESCE(SUM(o.payment.totalPriceCents), 0) AS totalSpentCents',
                'MAX(o.createdAt) AS lastOrderAt'
            )
            ->leftJoin(Order::class, 'o', 'WITH', 'o.user = u')
            ->groupBy('u.id');

        $this->applyAdminCustomerSearch($qb, $search);

        match ($sort) {
            'highest_spent' => $qb->orderBy('totalSpentCents', 'DESC')->addOrderBy('lastOrderAt', 'DESC'),
            'most_orders' => $qb->orderBy('ordersCount', 'DESC')->addOrderBy('lastOrderAt', 'DESC'),
            'newest_account' => $qb->orderBy('u.createdAt', 'DESC'),
            'name_asc' => $qb->orderBy('u.identity.lastName', 'ASC')->addOrderBy('u.identity.firstName', 'ASC'),
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

        $this->applyAdminCustomerSearch($qb, $search);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyAdminCustomerSearch(QueryBuilder $qb, ?string $search): void
    {
        $searchPattern = LikeSearchHelper::containsPattern($search);
        if (null === $searchPattern) {
            return;
        }

        $qb
            ->andWhere(
                $qb->expr()->orX(
                    'LOWER(u.identity.email) LIKE LOWER(:search)',
                    'LOWER(u.identity.firstName) LIKE LOWER(:search)',
                    'LOWER(u.identity.lastName) LIKE LOWER(:search)',
                    'LOWER(CONCAT(u.identity.firstName, \' \', u.identity.lastName)) LIKE LOWER(:search)',
                    'LOWER(u.identity.phoneNumber) LIKE LOWER(:search)',
                    'LOWER(o.number) LIKE LOWER(:search)',
                )
            )
            ->setParameter('search', $searchPattern);
    }
}
