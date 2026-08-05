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

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublishedListProjection(
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
        )
            ->select([
                'p.id AS id',
                'p.name AS name',
                'p.slug AS slug',
                'p.sku AS sku',
                'p.shortDescription AS shortDescription',
                'p.description AS description',
                'p.pricing.priceCents AS priceCents',
                'p.pricing.sellingType AS sellingType',
                'b.id AS brandId',
                'b.name AS brand',
                'p.characteristics.variantGroup AS variantGroup',
                'p.characteristics.variantPosition AS variantPosition',
                'p.characteristics.releaseYear AS releaseYear',
                'p.characteristics.storageCapacity AS storageCapacity',
                'p.characteristics.memoryRam AS memoryRam',
                'p.characteristics.color AS color',
                'p.inventory.stock AS stock',
                'p.publication.isPublished AS isPublished',
                'p.publication.isFeaturedHome AS isFeaturedHome',
                'p.imageName AS imageName',
                'p.imageAlt AS imageAlt',
                'p.galleryImage2Name AS galleryImage2Name',
                'p.galleryImage3Name AS galleryImage3Name',
                'p.galleryImage4Name AS galleryImage4Name',
                'p.reviewsCount AS reviewsCount',
                'p.reviewsAverage AS reviewsAverage',
                'p.discountEnabled AS discountEnabled',
                'p.discountType AS discountType',
                'p.discountValue AS discountValue',
                'p.discountStartsAt AS discountStartsAt',
                'p.discountEndsAt AS discountEndsAt',
                'p.createdAt AS createdAt',
                'p.updatedAt AS updatedAt',
                'c.id AS categoryId',
                'c.name AS categoryName',
                'c.slug AS categorySlug',
            ]);

        if (null !== $offset) {
            $qb->setFirstResult(max(0, $offset));
        }

        if (null !== $limit) {
            $qb->setMaxResults(max(1, $limit));
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        return $rows;
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
            'storageCapacities' => $this->collectFacetCounts(clone $base, 'p.characteristics.storageCapacity', 'storageCapacity'),
            'memoryRams' => $this->collectFacetCounts(clone $base, 'p.characteristics.memoryRam', 'memoryRam'),
            'colors' => $this->collectFacetCounts(clone $base, 'p.characteristics.color', 'color'),
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
            ->andWhere('p.publication.isPublished = :published')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('slug', $slug)
            ->setParameter('published', true)
            ->setParameter('visible', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
