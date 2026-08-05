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
    public function findPendingReviewItemsForUser(User $user): array
    {
        /** @var list<OrderItem> $items */
        $items = $this->createQueryBuilder('oi')
            ->addSelect('o', 'p')
            ->join('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
            ->andWhere('o.user = :user')
            ->andWhere('o.state.status = :status')
            ->andWhere('r.id IS NULL')
            ->andWhere('p IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('status', Order::STATUS_DELIVERED)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $items;
    }
}
