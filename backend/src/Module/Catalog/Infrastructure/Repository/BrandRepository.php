<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Application\Port\BrandRepositoryPort;

use App\Module\Catalog\Domain\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Brand>
 */
class BrandRepository extends ServiceEntityRepository implements BrandRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Brand
    {
        $brand = parent::find($id, $lockMode, $lockVersion);

        return $brand instanceof Brand ? $brand : null;
    }

    /**
     * @return list<Brand>
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function existsWithName(string $name, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('b')
            ->select('1')
            ->andWhere('LOWER(b.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb
                ->andWhere('b.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function findOneByName(string $name): ?Brand
    {
        return $this->createQueryBuilder('b')
            ->andWhere('LOWER(b.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
