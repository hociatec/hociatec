<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository implements OrderRepositoryPort
{
    use OrderAdminQueries;
    use OrderOperationsMetricsQueries;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Order
    {
        $order = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $order instanceof Order ? $order : null;
    }

    public function findForUpdate(int $id): ?Order
    {
        $order = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $order instanceof Order ? $order : null;
    }

    public function countForYear(int $year): int
    {
        $from = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $to = new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));

        $qb = $this->createQueryBuilder('o');
        $qb->select('COUNT(o.id)')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countInvoicedForYear(int $year): int
    {
        $from = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $to = new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));

        $qb = $this->createQueryBuilder('o');
        $qb->select('COUNT(o.id)')
            ->andWhere('o.invoice.invoicedAt BETWEEN :from AND :to')
            ->andWhere('o.invoice.number IS NOT NULL')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Order>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasActiveForUser(User $user): bool
    {
        return null !== $this->createQueryBuilder('o')
            ->select('1')
            ->andWhere('o.user = :user')
            ->andWhere('o.state.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
