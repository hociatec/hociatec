<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use Doctrine\ORM\QueryBuilder;

trait ProductPublishedQueryBuilder
{
    private function buildPublishedQuery(
        ProductCatalogCriteria $criteria,
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

        if (true === $criteria->onlyFeatured) {
            $qb
                ->andWhere('p.publication.isFeaturedHome = :featured')
                ->setParameter('featured', true);
        }

        $this->applyPublishedFilters($qb, $criteria);
        $this->applySearchFilter($qb, $criteria->search, $criteria->sort, $withSort);

        if ($withSort) {
            $this->applyPublishedSort($qb, $criteria->sort, $criteria->search);
        }

        return $qb;
    }
}
