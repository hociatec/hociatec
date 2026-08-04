<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use Doctrine\ORM\QueryBuilder;

trait ProductPublishedQueryBuilder
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
            ->andWhere('p.publication.isPublished = :published')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('published', true)
            ->setParameter('visible', true);

        if (true === $onlyFeatured) {
            $qb
                ->andWhere('p.publication.isFeaturedHome = :featured')
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
}
