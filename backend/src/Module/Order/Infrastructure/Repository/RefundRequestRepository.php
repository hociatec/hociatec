<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefundRequest>
 */
final class RefundRequestRepository extends ServiceEntityRepository implements RefundRequestRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundRequest::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);
    }

    public function findForUpdate(int $id): ?RefundRequest
    {
        $refund = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $refund instanceof RefundRequest ? $refund : null;
    }
}
