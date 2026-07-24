<?php

declare(strict_types=1);

namespace App\Module\Appointment\Repository;

use App\Module\Appointment\Entity\WorkingDayConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkingDayConfiguration>
 */
class WorkingDayConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkingDayConfiguration::class);
    }

    public function findOneByDay(int $dayOfWeek): ?WorkingDayConfiguration
    {
        return $this->findOneBy(['dayOfWeek' => $dayOfWeek]);
    }

    /**
     * @return list<WorkingDayConfiguration>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.dayOfWeek', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
