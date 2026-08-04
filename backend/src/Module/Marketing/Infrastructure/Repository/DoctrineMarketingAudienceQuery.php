<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Application\Port\MarketingAudienceQuery;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class DoctrineMarketingAudienceQuery implements MarketingAudienceQuery
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function resolveRecipients(string $segmentKey, array $criteria, ?int $limit = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT u')
            ->from(User::class, 'u')
            ->andWhere('u.isVerified = :verified')
            ->setParameter('verified', true)
            ->orderBy('u.createdAt', 'DESC');

        switch ($segmentKey) {
            case 'all_verified_users':
                break;
            case 'recent_verified_users':
                $days = max(7, (int) ($criteria['registeredDays'] ?? 30));
                $qb->andWhere('u.createdAt >= :threshold')
                    ->setParameter('threshold', new \DateTimeImmutable(sprintf('-%d days', $days)));
                break;
            case 'customers_with_orders':
                $this->withMinimumOrders($qb, max(1, (int) ($criteria['minimumOrders'] ?? 1)));
                break;
            case 'loyal_customers':
                $this->withMinimumOrders($qb, max(2, (int) ($criteria['minimumOrders'] ?? 3)));
                break;
            case 'single_order_customers':
                $qb->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('COUNT(o.id) = 1');
                break;
            case 'recent_customers':
                $days = max(7, (int) ($criteria['recentDays'] ?? 30));
                $qb->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->andWhere('o.createdAt >= :threshold')
                    ->setParameter('threshold', new \DateTimeImmutable(sprintf('-%d days', $days)));
                break;
            case 'high_value_customers':
                $minimum = max(1000, (int) ($criteria['minimumTotalCents'] ?? 50000));
                $qb->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('SUM(o.payment.totalPriceCents) >= :minimum')
                    ->setParameter('minimum', $minimum);
                break;
            case 'customers_without_review':
                $this->withMissingReviews($qb);
                break;
            case 'customers_with_pending_reviews':
                $this->withMissingReviews($qb);
                $qb->groupBy('u.id')
                    ->having('COUNT(DISTINCT oi.id) >= :minimum')
                    ->setParameter('minimum', max(1, (int) ($criteria['minimumPendingReviews'] ?? 2)));
                break;
            case 'inactive_customers':
                $days = max(30, (int) ($criteria['inactiveDays'] ?? 90));
                $qb->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('MAX(o.createdAt) < :threshold')
                    ->setParameter('threshold', new \DateTimeImmutable(sprintf('-%d days', $days)));
                break;
            case 'verified_without_orders':
                $this->withoutOrders($qb);
                break;
            case 'verified_without_orders_recent':
                $days = max(7, (int) ($criteria['registeredDays'] ?? 30));
                $this->withoutOrders($qb);
                $qb->andWhere('u.createdAt >= :threshold')
                    ->setParameter('threshold', new \DateTimeImmutable(sprintf('-%d days', $days)));
                break;
            default:
                throw new \InvalidArgumentException('Segment marketing inconnu.');
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var list<User> $users */
        $users = $qb->getQuery()->getResult();

        return $users;
    }

    private function withMinimumOrders(QueryBuilder $qb, int $minimum): void
    {
        $qb->join(Order::class, 'o', 'WITH', 'o.user = u')
            ->groupBy('u.id')
            ->having('COUNT(o.id) >= :minimum')
            ->setParameter('minimum', $minimum);
    }

    private function withMissingReviews(QueryBuilder $qb): void
    {
        $qb->join(Order::class, 'o', 'WITH', 'o.user = u')
            ->join(OrderItem::class, 'oi', 'WITH', 'oi.order = o')
            ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
            ->andWhere('r.id IS NULL');
    }

    private function withoutOrders(QueryBuilder $qb): void
    {
        $qb->leftJoin(Order::class, 'o', 'WITH', 'o.user = u')
            ->andWhere('o.id IS NULL');
    }
}
