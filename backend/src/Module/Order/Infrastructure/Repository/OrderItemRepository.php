<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\OrderItemRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository implements OrderItemRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    public function findById(int $id): ?OrderItem
    {
        $item = parent::find($id);

        return $item instanceof OrderItem ? $item : null;
    }

    public function findAdminRentalById(int $id): ?OrderItem
    {
        $item = $this->createQueryBuilder('oi')
            ->addSelect('o', 'u', 'p')
            ->join('oi.order', 'o')
            ->join('o.user', 'u')
            ->leftJoin('oi.product', 'p')
            ->andWhere('oi.id = :id')
            ->andWhere('oi.sellingType = :sellingType')
            ->setParameter('id', $id)
            ->setParameter('sellingType', 'rental')
            ->getQuery()
            ->getOneOrNullResult();

        return $item instanceof OrderItem ? $item : null;
    }

    /** @return list<OrderItem> */
    public function findPendingReviewItemsForUser(User $user, int $limit = 20, int $offset = 0): array
    {
        /** @var list<OrderItem> $items */
        $items = $this->createPendingReviewsQuery($user)
            ->addSelect('o', 'p')
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();

        return $items;
    }

    public function countPendingReviewItemsForUser(User $user): int
    {
        return (int) $this->createPendingReviewsQuery($user)
            ->select('COUNT(oi.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<OrderItem> */
    public function findUpcomingRentalsForUser(User $user, \DateTimeImmutable $today, int $limit = 20, int $offset = 0): array
    {
        /** @var list<OrderItem> $items */
        $items = $this->createRentalsQuery($user, $today, true)
            ->addSelect('o', 'p')
            ->orderBy('oi.rentalStartDate', 'ASC')
            ->addOrderBy('o.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();

        return $items;
    }

    /** @return list<OrderItem> */
    public function findPastRentalsForUser(User $user, \DateTimeImmutable $today, int $limit = 20, int $offset = 0): array
    {
        /** @var list<OrderItem> $items */
        $items = $this->createRentalsQuery($user, $today, false)
            ->addSelect('o', 'p')
            ->orderBy('oi.rentalEndDate', 'DESC')
            ->addOrderBy('o.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();

        return $items;
    }

    public function countUpcomingRentalsForUser(User $user, \DateTimeImmutable $today): int
    {
        return (int) $this->createRentalsQuery($user, $today, true)
            ->select('COUNT(oi.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPastRentalsForUser(User $user, \DateTimeImmutable $today): int
    {
        return (int) $this->createRentalsQuery($user, $today, false)
            ->select('COUNT(oi.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<OrderItem> */
    public function findRentalsForAdmin(
        ?string $search,
        ?string $timeline,
        ?string $requestStatus,
        ?string $requestType,
        \DateTimeImmutable $today,
        int $limit = 20,
        int $offset = 0,
    ): array {
        /** @var list<OrderItem> $items */
        $items = $this->createAdminRentalsQuery($search, $timeline, $requestStatus, $requestType, $today)
            ->addSelect('o', 'u', 'p')
            ->orderBy('oi.rentalStartDate', 'DESC')
            ->addOrderBy('o.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();

        return $items;
    }

    public function countRentalsForAdmin(
        ?string $search,
        ?string $timeline,
        ?string $requestStatus,
        ?string $requestType,
        \DateTimeImmutable $today,
    ): int {
        return (int) $this->createAdminRentalsQuery($search, $timeline, $requestStatus, $requestType, $today)
            ->select('COUNT(oi.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createPendingReviewsQuery(User $user): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('oi')
            ->join('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
            ->andWhere('o.user = :user')
            ->andWhere('o.state.status = :status')
            ->andWhere('r.id IS NULL')
            ->andWhere('p IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('status', Order::STATUS_DELIVERED);
    }

    private function createRentalsQuery(User $user, \DateTimeImmutable $today, bool $upcoming): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('oi')
            ->join('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->andWhere('o.user = :user')
            ->andWhere('oi.sellingType = :sellingType')
            ->setParameter('user', $user)
            ->setParameter('sellingType', 'rental');

        if ($upcoming) {
            $qb->andWhere('oi.rentalEndDate IS NULL OR oi.rentalEndDate >= :today');
        } else {
            $qb->andWhere('oi.rentalEndDate IS NOT NULL AND oi.rentalEndDate < :today');
        }

        return $qb->setParameter('today', $today->setTime(0, 0, 0));
    }

    private function createAdminRentalsQuery(
        ?string $search,
        ?string $timeline,
        ?string $requestStatus,
        ?string $requestType,
        \DateTimeImmutable $today,
    ): \Doctrine\ORM\QueryBuilder {
        $qb = $this->createQueryBuilder('oi')
            ->join('oi.order', 'o')
            ->join('o.user', 'u')
            ->leftJoin('oi.product', 'p')
            ->andWhere('oi.sellingType = :sellingType')
            ->setParameter('sellingType', 'rental');

        if (null !== $search && '' !== trim($search)) {
            $normalizedSearch = '%'.mb_strtolower(trim($search)).'%';
            $qb
                ->andWhere('LOWER(oi.productName) LIKE :search OR LOWER(oi.productSku) LIKE :search OR LOWER(o.number) LIKE :search OR LOWER(u.identity.email) LIKE :search OR LOWER(u.identity.firstName) LIKE :search OR LOWER(u.identity.lastName) LIKE :search')
                ->setParameter('search', $normalizedSearch);
        }

        switch ($timeline) {
            case 'upcoming':
                $qb->setParameter('today', $today->setTime(0, 0, 0));
                $qb->andWhere('oi.rentalStartDate IS NOT NULL AND oi.rentalStartDate > :today');
                break;
            case 'active':
                $qb->setParameter('today', $today->setTime(0, 0, 0));
                $qb
                    ->andWhere('oi.rentalStartDate IS NOT NULL AND oi.rentalStartDate <= :today')
                    ->andWhere('oi.rentalEndDate IS NULL OR oi.rentalEndDate >= :today');
                break;
            case 'past':
                $qb->setParameter('today', $today->setTime(0, 0, 0));
                $qb->andWhere('oi.rentalEndDate IS NOT NULL AND oi.rentalEndDate < :today');
                break;
        }

        if (null !== $requestStatus && 'all' !== $requestStatus) {
            $qb
                ->andWhere('oi.rentalRequestStatus = :requestStatus')
                ->setParameter('requestStatus', $requestStatus);
        }

        if (null !== $requestType && 'all' !== $requestType) {
            $qb
                ->andWhere('oi.rentalRequestType = :requestType')
                ->setParameter('requestType', $requestType);
        }

        return $qb;
    }
}
