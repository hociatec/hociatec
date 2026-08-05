<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use Doctrine\ORM\QueryBuilder;

trait ProductCatalogFilterQueries
{
    private const RELEVANCE_SCORE_SELECT = '(CASE WHEN LOWER(p.name) LIKE LOWER(:searchPrefix) THEN 120 ELSE 0 END
                    + CASE WHEN LOWER(p.sku) LIKE LOWER(:searchPrefix) THEN 100 ELSE 0 END
                    + CASE WHEN LOWER(b.name) LIKE LOWER(:searchPrefix) THEN 80 ELSE 0 END
                    + CASE WHEN LOWER(c.name) LIKE LOWER(:searchPrefix) THEN 60 ELSE 0 END
                    + CASE WHEN p.shortDescription IS NOT NULL AND LOWER(p.shortDescription) LIKE LOWER(:search) THEN 20 ELSE 0 END
                    + CASE WHEN LOWER(p.description) LIKE LOWER(:search) THEN 10 ELSE 0 END) AS HIDDEN relevanceScore';

    private function applyPublishedFilters(QueryBuilder $qb, ProductCatalogCriteria $criteria): void
    {
        if (null !== $criteria->categorySlug && '' !== $criteria->categorySlug) {
            $qb
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $criteria->categorySlug);
        }

        if (null !== $criteria->sellingType && in_array($criteria->sellingType, ['sale', 'rental'], true)) {
            $qb
                ->andWhere('p.pricing.sellingType = :stype')
                ->setParameter('stype', $criteria->sellingType);
        }

        $this->applyExactLowerFilter($qb, 'b.name', 'brand', $criteria->brand);
        $this->applyExactLowerFilter($qb, 'p.characteristics.storageCapacity', 'storageCapacity', $criteria->storageCapacity);
        $this->applyExactLowerFilter($qb, 'p.characteristics.memoryRam', 'memoryRam', $criteria->memoryRam);
        $this->applyExactLowerFilter($qb, 'p.characteristics.color', 'color', $criteria->color);

        if (null !== $criteria->minPriceCents && $criteria->minPriceCents >= 0) {
            $qb
                ->andWhere('p.pricing.priceCents >= :minPriceCents')
                ->setParameter('minPriceCents', $criteria->minPriceCents);
        }

        if (null !== $criteria->maxPriceCents && $criteria->maxPriceCents >= 0) {
            $qb
                ->andWhere('p.pricing.priceCents <= :maxPriceCents')
                ->setParameter('maxPriceCents', $criteria->maxPriceCents);
        }

        if (true === $criteria->inStockOnly) {
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
            $this->addRelevanceScoreSelect($qb, $normalizedSearch);
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

    private function addRelevanceScoreSelect(QueryBuilder $qb, string $normalizedSearch): void
    {
        $qb
            ->addSelect(self::RELEVANCE_SCORE_SELECT)
            ->setParameter('searchPrefix', sprintf('%s%%', $normalizedSearch));
    }
}
