<?php

declare(strict_types=1);

namespace App\Module\Order\Repository;

use App\Module\Order\Entity\RefundRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefundRequest>
 */
final class RefundRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundRequest::class);
    }

    public function findForUpdate(int $id): ?RefundRequest
    {
        $refund = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $refund instanceof RefundRequest ? $refund : null;
    }
}
