<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Application\Port\MarketingRecipientContextQuery;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineMarketingRecipientContextQuery implements MarketingRecipientContextQuery
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function orderStats(User $user): array
    {
        /** @var array{ordersCount:int|string, totalSpentCents:int|string, lastOrderAt?:?\DateTimeInterface} $stats */
        $stats = $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id) AS ordersCount', 'MAX(o.createdAt) AS lastOrderAt', 'COALESCE(SUM(o.totalPriceCents), 0) AS totalSpentCents')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        return $stats;
    }

    public function lastOrder(User $user): ?object
    {
        $order = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $order instanceof Order ? $order : null;
    }

    public function pendingReviewsCount(User $user): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT oi.id)')
            ->from(Order::class, 'o')
            ->join(OrderItem::class, 'oi', 'WITH', 'oi.order = o')
            ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
            ->andWhere('o.user = :user')
            ->andWhere('r.id IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
