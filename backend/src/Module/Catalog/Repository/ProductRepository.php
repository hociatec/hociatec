<?php

declare(strict_types=1);

namespace App\Module\Catalog\Repository;

use App\Module\Catalog\Entity\Brand;
use App\Module\Catalog\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    use ProductRepositoryQueryHelpers;

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
    public function findAllForAdmin(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $lowStockOnly = null,
        ?string $sort = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $qb = $this->createAdminQuery(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $minPriceCents,
            $maxPriceCents,
            $lowStockOnly,
            $sort,
        );

        if (null !== $offset) {
            $qb->setFirstResult(max(0, $offset));
        }

        if (null !== $limit) {
            $qb->setMaxResults(max(1, $limit));
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $lowStockOnly = null,
    ): int {
        return (int) $this->createAdminQuery(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $minPriceCents,
            $maxPriceCents,
            $lowStockOnly,
            null,
        )
            ->select('COUNT(p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createAdminQuery(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured,
        ?string $sellingType,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?bool $lowStockOnly,
        ?string $sort,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('c', 'b')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.brandReference', 'b');

        if (true === $onlyFeatured) {
            $qb->andWhere('p.isFeaturedHome = :featured')->setParameter('featured', true);
        }

        if (null !== $categorySlug && '' !== $categorySlug) {
            $qb->andWhere('c.slug = :adminCategory')->setParameter('adminCategory', $categorySlug);
        }

        if (null !== $sellingType && in_array($sellingType, ['sale', 'rental'], true)) {
            $qb->andWhere('p.sellingType = :adminSellingType')->setParameter('adminSellingType', $sellingType);
        }

        if (null !== $minPriceCents && $minPriceCents >= 0) {
            $qb->andWhere('p.priceCents >= :adminMinPrice')->setParameter('adminMinPrice', $minPriceCents);
        }

        if (null !== $maxPriceCents && $maxPriceCents >= 0) {
            $qb->andWhere('p.priceCents <= :adminMaxPrice')->setParameter('adminMaxPrice', $maxPriceCents);
        }

        if (true === $lowStockOnly) {
            $qb->andWhere('p.stock <= p.lowStockThreshold');
        }

        $this->applySearchFilter($qb, $search, $sort, null !== $sort);
        $this->applyPublishedSort($qb, $sort, $search);

        return $qb;
    }

    /**
     * @return list<Product>
     */
    public function findPublished(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
        ?string $sort = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $qb = $this->buildPublishedQuery(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
            $sort,
            true,
        );

        if (null !== $offset) {
            $qb->setFirstResult(max(0, $offset));
        }

        if (null !== $limit) {
            $qb->setMaxResults(max(1, $limit));
        }

        return $qb->getQuery()->getResult();
    }

    public function countPublished(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): int {
        $qb = $this->buildPublishedQuery(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
            null,
            false,
        );

        return (int) $qb
            ->select('COUNT(p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, mixed>
     */
    public function collectPublishedFacets(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): array {
        $base = $this->buildPublishedQuery(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
            null,
            false,
        );

        return [
            'brands' => $this->collectFacetCounts(clone $base, 'b.name', 'brandName'),
            'categories' => $this->collectFacetCounts(clone $base, 'c.name', 'categoryName', 'c.slug'),
            'storageCapacities' => $this->collectFacetCounts(clone $base, 'p.storageCapacity', 'storageCapacity'),
            'memoryRams' => $this->collectFacetCounts(clone $base, 'p.memoryRam', 'memoryRam'),
            'colors' => $this->collectFacetCounts(clone $base, 'p.color', 'color'),
            'price' => $this->collectPriceBounds(clone $base),
        ];
    }

    public function findOnePublishedBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c', 'b')
            ->join('p.category', 'c')
            ->leftJoin('p.brandReference', 'b')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.isPublished = :published')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('slug', $slug)
            ->setParameter('published', true)
            ->setParameter('visible', true)
            ->getQuery()
            ->getOneOrNullResult();
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
                'UPDATE App\Module\Catalog\Entity\Product p
                SET p.brandReference = NULL
                WHERE p.brandReference = :brand'
            )
            ->setParameter('brand', $brand)
            ->execute();
    }

    public function countLowStock(int $threshold = 3): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.stock <= COALESCE(p.lowStockThreshold, :threshold)')
            ->andWhere('p.isPublished = :published')
            ->setParameter('threshold', max(0, $threshold))
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Product>
     */
    public function findLowStock(int $threshold = 3, int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c', 'b')
            ->join('p.category', 'c')
            ->leftJoin('p.brandReference', 'b')
            ->andWhere('p.stock <= COALESCE(p.lowStockThreshold, :threshold)')
            ->andWhere('p.isPublished = :published')
            ->setParameter('threshold', max(0, $threshold))
            ->setParameter('published', true)
            ->orderBy('p.stock', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
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
