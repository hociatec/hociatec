<?php

declare(strict_types=1);

namespace App\Module\Support\Repository;

use App\Module\Support\Entity\SupportRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportRequest>
 */
final class SupportRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportRequest::class);
    }
}
