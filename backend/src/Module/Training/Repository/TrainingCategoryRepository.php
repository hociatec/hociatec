<?php

declare(strict_types=1);

namespace App\Module\Training\Repository;

use App\Module\Training\Entity\TrainingCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TrainingCategory> */
class TrainingCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingCategory::class);
    }

    /** @return list<TrainingCategory> */
    public function findOrdered(bool $activeOnly = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('c.isActive = true');
        }

        return $qb->getQuery()->getResult();
    }
}
