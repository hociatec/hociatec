<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderCheckoutSession>
 */
final class OrderCheckoutSessionRepository extends ServiceEntityRepository implements OrderCheckoutSessionRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderCheckoutSession::class);
    }

    public function findOneByStripeSessionId(string $stripeSessionId): ?OrderCheckoutSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.payment.stripeSessionId = :stripeSessionId')
            ->setParameter('stripeSessionId', $stripeSessionId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByStripePaymentIntentId(string $stripePaymentIntentId): ?OrderCheckoutSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.payment.stripePaymentIntentId = :stripePaymentIntentId')
            ->setParameter('stripePaymentIntentId', $stripePaymentIntentId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByOrderId(int $orderId): ?OrderCheckoutSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.lifecycle.orderId = :orderId')
            ->setParameter('orderId', $orderId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<OrderCheckoutSession>
     */
    public function findRecentByOrderId(int $orderId, int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.lifecycle.orderId = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
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
            ->andWhere('s.lifecycle.status = :status')
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
            ->andWhere('s.lifecycle.orderId = :orderId')
            ->andWhere('s.lifecycle.status = :status')
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
            ->select('s.lifecycle.status AS status', 'COUNT(s.id) AS total')
            ->groupBy('s.lifecycle.status')
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
            ->andWhere('s.lifecycle.status = :status')
            ->andWhere('s.lifecycle.orderId IS NULL')
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
            ->andWhere('s.lifecycle.status IN (:statuses) OR (s.lifecycle.status = :paidStatus AND s.lifecycle.orderId IS NULL)')
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

    /**
     * @return list<OrderCheckoutSession>
     */
    public function findRecentOpen(int $limit = 20): array
    {
        /** @var list<OrderCheckoutSession> $items */
        $items = $this->createQueryBuilder('s')
            ->andWhere('s.lifecycle.status = :status')
            ->setParameter('status', OrderCheckoutSession::STATUS_OPEN)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }
}
