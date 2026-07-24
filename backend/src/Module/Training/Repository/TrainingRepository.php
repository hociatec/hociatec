<?php

declare(strict_types=1);

namespace App\Module\Training\Repository;

use App\Module\Training\Entity\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Training> */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    /** @return list<Training> */
    public function findActive(?string $category = null): array
    {
        $criteria = ['isActive' => true];
        if (null !== $category && '' !== $category) {
            $criteria['category'] = $category;
        }

        return $this->findBy($criteria, ['title' => 'ASC']);
    }
}
