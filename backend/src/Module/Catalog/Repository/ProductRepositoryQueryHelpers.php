<?php

declare(strict_types=1);

namespace App\Module\Catalog\Repository;

use Doctrine\ORM\QueryBuilder;

trait ProductRepositoryQueryHelpers
{
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

        $this->applyPublishedFilters(
            $qb,
            $categorySlug,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
        );
        $this->applySearchFilter($qb, $search, $sort, $withSort);

        if ($withSort) {
            $this->applyPublishedSort($qb, $sort, $search);
        }

        return $qb;
    }

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
                ->andWhere('p.sellingType = :stype')
                ->setParameter('stype', $sellingType);
        }

        $this->applyExactLowerFilter($qb, 'b.name', 'brand', $brand);
        $this->applyExactLowerFilter($qb, 'p.storageCapacity', 'storageCapacity', $storageCapacity);
        $this->applyExactLowerFilter($qb, 'p.memoryRam', 'memoryRam', $memoryRam);
        $this->applyExactLowerFilter($qb, 'p.color', 'color', $color);

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
            $qb->andWhere('p.stock > 0');
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

    private function applyExactLowerFilter(QueryBuilder $qb, string $field, string $parameter, ?string $value): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $qb
            ->andWhere(sprintf('LOWER(%s) = LOWER(:%s)', $field, $parameter))
            ->setParameter($parameter, trim($value));
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
