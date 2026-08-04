<?php

declare(strict_types=1);

namespace App\Module\Appointment\Infrastructure\Repository;

use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
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

    public function findOneByDayForUpdate(int $dayOfWeek): ?WorkingDayConfiguration
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.dayOfWeek = :dayOfWeek')
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
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
