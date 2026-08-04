<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderCheckoutSession>
 */
final class OrderCheckoutSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderCheckoutSession::class);
    }

    public function findOneByStripeSessionId(string $stripeSessionId): ?OrderCheckoutSession
    {
        return $this->findOneBy(['stripeSessionId' => $stripeSessionId]);
    }

    public function findOneByStripePaymentIntentId(string $stripePaymentIntentId): ?OrderCheckoutSession
    {
        return $this->findOneBy(['stripePaymentIntentId' => $stripePaymentIntentId]);
    }

    public function findOneByToken(string $token): ?OrderCheckoutSession
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findReusableOpenSessionForCart(User $user, string $cartToken): ?OrderCheckoutSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.cartToken = :cartToken')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('cartToken', $cartToken)
            ->setParameter('status', OrderCheckoutSession::STATUS_OPEN)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findReusableOpenSessionForOrder(User $user, int $orderId): ?OrderCheckoutSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.orderId = :orderId')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('orderId', $orderId)
            ->setParameter('status', OrderCheckoutSession::STATUS_OPEN)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<string, int>
     */
    public function getStatusCounts(): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.status AS status', 'COUNT(s.id) AS total')
            ->groupBy('s.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [
            OrderCheckoutSession::STATUS_OPEN => 0,
            OrderCheckoutSession::STATUS_PAID => 0,
            OrderCheckoutSession::STATUS_EXPIRED => 0,
            OrderCheckoutSession::STATUS_FAILED => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $counts;
    }

    public function countPaidWithoutOrder(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->andWhere('s.orderId IS NULL')
            ->setParameter('status', OrderCheckoutSession::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<OrderCheckoutSession>
     */
    public function findRecentForDashboard(int $limit = 6): array
    {
        /** @var list<OrderCheckoutSession> $items */
        $items = $this->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    /**
     * @return list<OrderCheckoutSession>
     */
    public function findAttentionItemsForDashboard(int $limit = 6): array
    {
        /** @var list<OrderCheckoutSession> $items */
        $items = $this->createQueryBuilder('s')
            ->andWhere('s.status IN (:statuses) OR (s.status = :paidStatus AND s.orderId IS NULL)')
            ->setParameter('statuses', [
                OrderCheckoutSession::STATUS_FAILED,
                OrderCheckoutSession::STATUS_EXPIRED,
            ])
            ->setParameter('paidStatus', OrderCheckoutSession::STATUS_PAID)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }
}
