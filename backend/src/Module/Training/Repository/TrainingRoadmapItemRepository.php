<?php

declare(strict_types=1);

namespace App\Module\Training\Repository;

use App\Module\Training\Entity\TrainingRoadmapItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TrainingRoadmapItem> */
class TrainingRoadmapItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingRoadmapItem::class);
    }
}
