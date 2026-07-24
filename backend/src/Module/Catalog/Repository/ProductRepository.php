<?php

declare(strict_types=1);

namespace App\Module\Catalog\Repository;

use App\Module\Catalog\Entity\Brand;
use App\Module\Catalog\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return list<Product>
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c', 'b')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.brandReference', 'b')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
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

    private function buildPublishedQuery(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured,
        ?string $sellingType,
        ?string $brand,
        ?string $storageCapacity,
        ?string $memoryRam,
        ?string $color,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?bool $inStockOnly,
        ?string $sort,
        bool $withSort,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('c', 'b')
            ->join('p.category', 'c')
            ->leftJoin('p.brandReference', 'b')
            ->andWhere('p.isPublished = :published')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('published', true)
            ->setParameter('visible', true);

        if (true === $onlyFeatured) {
            $qb
                ->andWhere('p.isFeaturedHome = :featured')
                ->setParameter('featured', true);
        }

        if (null !== $categorySlug && '' !== $categorySlug) {
            $qb
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $categorySlug);
        }

        if (null !== $search && '' !== $search) {
            $normalizedSearch = mb_strtolower(trim($search));
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'LOWER(p.name) LIKE LOWER(:search)',
                        'p.shortDescription IS NOT NULL AND LOWER(p.shortDescription) LIKE LOWER(:search)',
                        'LOWER(p.description) LIKE LOWER(:search)',
                        'LOWER(p.sku) LIKE LOWER(:search)',
                        'b.name IS NOT NULL AND LOWER(b.name) LIKE LOWER(:search)',
                        'LOWER(c.name) LIKE LOWER(:search)'
                    )
                )
                ->setParameter('search', sprintf('%%%s%%', $normalizedSearch));

            if ($withSort && 'relevance' === $sort) {
                $qb
                    ->addSelect(
                        '(CASE WHEN LOWER(p.name) LIKE LOWER(:searchPrefix) THEN 120 ELSE 0 END
                        + CASE WHEN LOWER(p.sku) LIKE LOWER(:searchPrefix) THEN 100 ELSE 0 END
                        + CASE WHEN LOWER(b.name) LIKE LOWER(:searchPrefix) THEN 80 ELSE 0 END
                        + CASE WHEN LOWER(c.name) LIKE LOWER(:searchPrefix) THEN 60 ELSE 0 END
                        + CASE WHEN p.shortDescription IS NOT NULL AND LOWER(p.shortDescription) LIKE LOWER(:search) THEN 20 ELSE 0 END
                        + CASE WHEN LOWER(p.description) LIKE LOWER(:search) THEN 10 ELSE 0 END) AS HIDDEN relevanceScore'
                    )
                    ->setParameter('searchPrefix', sprintf('%s%%', $normalizedSearch));
            }
        }

        if (null !== $sellingType && in_array($sellingType, ['sale', 'rental'], true)) {
            $qb
                ->andWhere('p.sellingType = :stype')
                ->setParameter('stype', $sellingType);
        }

        if (null !== $brand && '' !== $brand) {
            $qb
                ->andWhere('LOWER(b.name) = LOWER(:brand)')
                ->setParameter('brand', trim($brand));
        }

        if (null !== $storageCapacity && '' !== $storageCapacity) {
            $qb
                ->andWhere('LOWER(p.storageCapacity) = LOWER(:storageCapacity)')
                ->setParameter('storageCapacity', trim($storageCapacity));
        }

        if (null !== $memoryRam && '' !== $memoryRam) {
            $qb
                ->andWhere('LOWER(p.memoryRam) = LOWER(:memoryRam)')
                ->setParameter('memoryRam', trim($memoryRam));
        }

        if (null !== $color && '' !== $color) {
            $qb
                ->andWhere('LOWER(p.color) = LOWER(:color)')
                ->setParameter('color', trim($color));
        }

        if (null !== $minPriceCents && $minPriceCents >= 0) {
            $qb
                ->andWhere('p.priceCents >= :minPriceCents')
                ->setParameter('minPriceCents', $minPriceCents);
        }

        if (null !== $maxPriceCents && $maxPriceCents >= 0) {
            $qb
                ->andWhere('p.priceCents <= :maxPriceCents')
                ->setParameter('maxPriceCents', $maxPriceCents);
        }

        if (true === $inStockOnly) {
            $qb
                ->andWhere('p.stock > 0');
        }

        if ($withSort) {
            match ($sort) {
                'relevance' => null !== $search && '' !== trim($search)
                    ? $qb->orderBy('relevanceScore', 'DESC')->addOrderBy('p.name', 'ASC')
                    : $qb->orderBy('p.name', 'ASC'),
                'price_asc' => $qb->orderBy('p.priceCents', 'ASC')->addOrderBy('p.name', 'ASC'),
                'price_desc' => $qb->orderBy('p.priceCents', 'DESC')->addOrderBy('p.name', 'ASC'),
                'release_year_desc' => $qb->orderBy('p.releaseYear', 'DESC')->addOrderBy('p.name', 'ASC'),
                'release_year_asc' => $qb->orderBy('p.releaseYear', 'ASC')->addOrderBy('p.name', 'ASC'),
                'name_desc' => $qb->orderBy('p.name', 'DESC'),
                'stock_desc' => $qb->orderBy('p.stock', 'DESC')->addOrderBy('p.name', 'ASC'),
                'stock_asc' => $qb->orderBy('p.stock', 'ASC')->addOrderBy('p.name', 'ASC'),
                'created_desc' => $qb->orderBy('p.createdAt', 'DESC')->addOrderBy('p.name', 'ASC'),
                default => $qb->orderBy('p.name', 'ASC'),
            };
        }

        return $qb;
    }

    /**
     * @return list<array{value: string, count: int, extra?: string|null}>
     */
    private function collectFacetCounts(QueryBuilder $qb, string $valueExpression, string $valueAlias, ?string $secondaryExpression = null): array
    {
        $select = [
            sprintf('%s AS value', $valueExpression),
            'COUNT(p.id) AS count',
        ];

        if (null !== $secondaryExpression) {
            $select[] = sprintf('%s AS extra', $secondaryExpression);
        }

        $rows = $qb
            ->resetDQLPart('orderBy')
            ->select(implode(', ', $select))
            ->andWhere(sprintf('%s IS NOT NULL', $valueExpression))
            ->andWhere(sprintf("%s != ''", $valueExpression))
            ->groupBy($valueExpression)
            ->orderBy($valueExpression, 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(
            static function (array $row) use ($secondaryExpression): array {
                $item = [
                    'value' => (string) ($row['value'] ?? ''),
                    'count' => isset($row['count']) ? (int) $row['count'] : 0,
                ];

                if (null !== $secondaryExpression) {
                    $item['extra'] = isset($row['extra']) ? (string) $row['extra'] : null;
                }

                return $item;
            },
            $rows,
        ));
    }

    /**
     * @return array{min:int|null,max:int|null}
     */
    private function collectPriceBounds(QueryBuilder $qb): array
    {
        $row = $qb
            ->resetDQLPart('orderBy')
            ->select('MIN(p.priceCents) AS minPrice, MAX(p.priceCents) AS maxPrice')
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'min' => isset($row['minPrice']) ? (int) $row['minPrice'] : null,
            'max' => isset($row['maxPrice']) ? (int) $row['maxPrice'] : null,
        ];
    }
}
