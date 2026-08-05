<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderCheckoutSession>
 */
final class OrderCheckoutSessionRepository extends ServiceEntityRepository implements OrderCheckoutSessionRepositoryPort
{
    use OrderCheckoutSessionAdminQueries;
    use OrderCheckoutSessionDashboardQueries;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderCheckoutSession::class);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?OrderCheckoutSession
    {
        $session = parent::find($id, $lockMode, $lockVersion);

        return $session instanceof OrderCheckoutSession ? $session : null;
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

}
