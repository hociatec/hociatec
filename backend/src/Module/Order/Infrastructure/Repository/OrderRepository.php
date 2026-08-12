<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use App\Shared\Infrastructure\Persistence\LikeSearchHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findByUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('o')
            ->addSelect('i', 'p')
            ->leftJoin('o.items', 'i')
            ->leftJoin('i.product', 'p')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Order>
     */
    public function findForUserList(User $user, ?string $status, ?string $search, int $limit, int $offset): array
    {
        /** @var list<Order> $orders */
        $orders = $this->createUserListQuery($user, $status, $search)
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();

        return $orders;
    }

    public function countForUserList(User $user, ?string $status, ?string $search): int
    {
        return (int) $this->createUserListQuery($user, $status, $search)
            ->select('COUNT(DISTINCT o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array{all:int,open:int,delivered:int,cancelled:int} */
    public function countStatusBucketsForUser(User $user): array
    {
        $all = $this->countByUser($user);
        $open = $this->countForUserList($user, 'open', null);
        $delivered = $this->countForUserList($user, Order::STATUS_DELIVERED, null);
        $cancelled = $this->countForUserList($user, Order::STATUS_CANCELLED, null);

        return [
            'all' => $all,
            'open' => $open,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
        ];
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

    private function createUserListQuery(User $user, ?string $status, ?string $search): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->addSelect('i', 'p')
            ->leftJoin('o.items', 'i')
            ->leftJoin('i.product', 'p')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user);

        $searchPattern = LikeSearchHelper::containsPattern($search, true);
        if (null !== $searchPattern) {
            $qb
                ->andWhere('LOWER(o.number) LIKE :search OR LOWER(COALESCE(i.productName, \'\')) LIKE :search OR LOWER(COALESCE(i.productSku, \'\')) LIKE :search OR LOWER(COALESCE(o.billing.billingName, \'\')) LIKE :search OR LOWER(COALESCE(o.billing.billingEmail, \'\')) LIKE :search')
                ->setParameter('search', $searchPattern);
        }

        if (null === $status || '' === $status || 'all' === $status) {
            return $qb;
        }

        if ('open' === $status) {
            return $qb
                ->andWhere('o.state.status IN (:statuses)')
                ->setParameter('statuses', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED]);
        }

        return $qb
            ->andWhere('o.state.status = :status')
            ->setParameter('status', $status);
    }

    /**
     * @return list<Order>
     */
    public function findWithInvoiceDocumentsAfterId(int $lastId, int $limit): array
    {
        /** @var list<Order> $orders */
        $orders = $this->createQueryBuilder('o')
            ->andWhere('o.id > :lastId')
            ->andWhere('o.invoice.pdfPath IS NOT NULL OR o.invoice.xmlPath IS NOT NULL')
            ->setParameter('lastId', max(0, $lastId))
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(max(1, min(1000, $limit)))
            ->getQuery()
            ->getResult();

        return $orders;
    }
}
