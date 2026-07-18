<?php

declare(strict_types=1);

namespace App\Module\Catalog\Repository;

use App\Module\Catalog\Entity\StockMovement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockMovement>
 */
final class StockMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockMovement::class);
    }
}
