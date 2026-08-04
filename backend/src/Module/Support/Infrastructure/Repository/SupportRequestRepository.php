<?php

declare(strict_types=1);

namespace App\Module\Support\Infrastructure\Repository;

use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\Support\Domain\Entity\SupportRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportRequest>
 */
final class SupportRequestRepository extends ServiceEntityRepository implements SupportRequestRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportRequest::class);
    }
}
