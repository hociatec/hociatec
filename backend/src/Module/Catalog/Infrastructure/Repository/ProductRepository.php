<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    use ProductAdminQueries;
    use ProductPublicQueries;
    use ProductRepositoryQueryHelpers;
    use ProductStockQueries;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findForUpdate(int $id): ?Product
    {
        $product = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $product instanceof Product ? $product : null;
    }

    /**
     * @return list<Product>
     */
    public function findByVariantGroupOrdered(string $variantGroup): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.variantGroup = :variantGroup')
            ->setParameter('variantGroup', $variantGroup)
            ->orderBy('p.variantPosition', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByBrand(Brand $brand): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.brandReference = :brand')
            ->setParameter('brand', $brand)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function clearBrand(Brand $brand): void
    {
        $this->getEntityManager()
            ->createQuery(
                'UPDATE App\Module\Catalog\Domain\Entity\Product p
                SET p.brandReference = NULL
                WHERE p.brandReference = :brand'
            )
            ->setParameter('brand', $brand)
            ->execute();
    }

    public function existsWithSku(string $sku, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('1')
            ->andWhere('LOWER(p.sku) = LOWER(:sku)')
            ->setParameter('sku', $sku)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb
                ->andWhere('p.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function existsWithSlug(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('1')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb
                ->andWhere('p.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }
}
