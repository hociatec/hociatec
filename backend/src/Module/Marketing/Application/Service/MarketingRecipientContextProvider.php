<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Service;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class MarketingRecipientContextProvider
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private string $frontendUrl,
    ) {
    }

    /** @return array<string, string> */
    public function provide(User $user): array
    {
        $orderStats = $this->persistence->queryBuilder()
            ->select('COUNT(o.id) AS ordersCount', 'MAX(o.createdAt) AS lastOrderAt', 'COALESCE(SUM(o.totalPriceCents), 0) AS totalSpentCents')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();
        $lastOrder = $this->persistence->queryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $pendingReviews = (int) $this->persistence->queryBuilder()
            ->select('COUNT(DISTINCT oi.id)')
            ->from(Order::class, 'o')
            ->join(OrderItem::class, 'oi', 'WITH', 'oi.order = o')
            ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
            ->andWhere('o.user = :user')
            ->andWhere('r.id IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'order_count' => (string) ((int) ($orderStats['ordersCount'] ?? 0)),
            'total_spent_eur' => number_format(((int) ($orderStats['totalSpentCents'] ?? 0)) / 100, 2, ',', ' '),
            'last_order_date' => $orderStats['lastOrderAt'] instanceof \DateTimeInterface
                ? $orderStats['lastOrderAt']->format('d/m/Y')
                : '',
            'last_order_number' => $lastOrder instanceof Order ? $lastOrder->getNumber() : '',
            'days_since_last_order' => $orderStats['lastOrderAt'] instanceof \DateTimeInterface
                ? (string) (new \DateTimeImmutable())->diff(\DateTimeImmutable::createFromInterface($orderStats['lastOrderAt']))->days
                : '',
            'pending_reviews_count' => (string) $pendingReviews,
            'app_frontend_url' => rtrim($this->frontendUrl, '/'),
        ];
    }
}
