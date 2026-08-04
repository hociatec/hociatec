<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Domain\Entity\Product;

trait ProductPublicQueries
{
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
}
