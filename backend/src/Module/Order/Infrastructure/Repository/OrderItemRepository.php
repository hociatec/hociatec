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
}
