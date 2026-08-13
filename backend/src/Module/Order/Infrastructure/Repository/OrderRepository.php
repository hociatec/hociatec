<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository implements OrderRepositoryPort
{
    use OrderAccountingQueries;
    use OrderAdminQueries;
    use OrderCustomerQueries;
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
