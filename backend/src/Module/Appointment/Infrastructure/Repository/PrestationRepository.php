<?php

declare(strict_types=1);

namespace App\Module\Appointment\Infrastructure\Repository;

use App\Module\Appointment\Application\Port\PrestationRepositoryPort;

use App\Module\Appointment\Domain\Entity\Prestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prestation>
 */
class PrestationRepository extends ServiceEntityRepository implements PrestationRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prestation::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Prestation
    {
        $prestation = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $prestation instanceof Prestation ? $prestation : null;
    }

    /**
     * @return list<Prestation>
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function remove(Prestation $prestation): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($prestation);
    }
}
