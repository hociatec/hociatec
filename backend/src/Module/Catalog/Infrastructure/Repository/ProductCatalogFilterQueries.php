<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use Doctrine\ORM\QueryBuilder;

trait ProductCatalogFilterQueries
{
    private function applyPublishedFilters(
        QueryBuilder $qb,
        ?string $categorySlug,
        ?string $sellingType,
        ?string $brand,
        ?string $storageCapacity,
        ?string $memoryRam,
        ?string $color,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?bool $inStockOnly,
    ): void {
        if (null !== $categorySlug && '' !== $categorySlug) {
            $qb
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $categorySlug);
        }

        if (null !== $sellingType && in_array($sellingType, ['sale', 'rental'], true)) {
            $qb
                ->andWhere('p.pricing.sellingType = :stype')
                ->setParameter('stype', $sellingType);
        }

        $this->applyExactLowerFilter($qb, 'b.name', 'brand', $brand);
        $this->applyExactLowerFilter($qb, 'p.characteristics.storageCapacity', 'storageCapacity', $storageCapacity);
        $this->applyExactLowerFilter($qb, 'p.characteristics.memoryRam', 'memoryRam', $memoryRam);
        $this->applyExactLowerFilter($qb, 'p.characteristics.color', 'color', $color);

        if (null !== $minPriceCents && $minPriceCents >= 0) {
            $qb
                ->andWhere('p.pricing.priceCents >= :minPriceCents')
                ->setParameter('minPriceCents', $minPriceCents);
        }

        if (null !== $maxPriceCents && $maxPriceCents >= 0) {
            $qb
                ->andWhere('p.pricing.priceCents <= :maxPriceCents')
                ->setParameter('maxPriceCents', $maxPriceCents);
        }

        if (true === $inStockOnly) {
            $qb->andWhere('p.inventory.stock > 0');
        }
    }

    private function applySearchFilter(QueryBuilder $qb, ?string $search, ?string $sort, bool $withSort): void
    {
        if (null === $search || '' === $search) {
            return;
        }

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

    private function applyPublishedSort(QueryBuilder $qb, ?string $sort, ?string $search): void
    {
        match ($sort) {
            'relevance' => null !== $search && '' !== trim($search)
                ? $qb->orderBy('relevanceScore', 'DESC')->addOrderBy('p.name', 'ASC')
                : $qb->orderBy('p.name', 'ASC'),
            'price_asc' => $qb->orderBy('p.pricing.priceCents', 'ASC')->addOrderBy('p.name', 'ASC'),
            'price_desc' => $qb->orderBy('p.pricing.priceCents', 'DESC')->addOrderBy('p.name', 'ASC'),
            'release_year_desc' => $qb->orderBy('p.characteristics.releaseYear', 'DESC')->addOrderBy('p.name', 'ASC'),
            'release_year_asc' => $qb->orderBy('p.characteristics.releaseYear', 'ASC')->addOrderBy('p.name', 'ASC'),
            'name_desc' => $qb->orderBy('p.name', 'DESC'),
            'stock_desc' => $qb->orderBy('p.inventory.stock', 'DESC')->addOrderBy('p.name', 'ASC'),
            'stock_asc' => $qb->orderBy('p.inventory.stock', 'ASC')->addOrderBy('p.name', 'ASC'),
            'created_desc' => $qb->orderBy('p.createdAt', 'DESC')->addOrderBy('p.name', 'ASC'),
            default => $qb->orderBy('p.name', 'ASC'),
        };
    }

    private function applyExactLowerFilter(QueryBuilder $qb, string $field, string $parameter, ?string $value): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $qb
            ->andWhere(sprintf('LOWER(%s) = LOWER(:%s)', $field, $parameter))
            ->setParameter($parameter, trim($value));
    }
}
