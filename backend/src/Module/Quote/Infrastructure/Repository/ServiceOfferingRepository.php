<?php

declare(strict_types=1);

namespace App\Module\Quote\Infrastructure\Repository;

use App\Module\Quote\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceOffering>
 */
class ServiceOfferingRepository extends ServiceEntityRepository implements ServiceOfferingRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceOffering::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?ServiceOffering
    {
        $service = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $service instanceof ServiceOffering ? $service : null;
    }

    /** @return list<ServiceOffering> */
    public function findPaginated(int $limit, int $offset): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.title', 'ASC')
            ->setMaxResults(max(1, min(100, $limit)))
            ->setFirstResult(max(0, $offset))
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function delete(ServiceOffering $service): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($service);
    }
}
