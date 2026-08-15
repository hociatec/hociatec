<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Application\Query\ProductAdminCriteria;
use App\Module\Catalog\Domain\Entity\Product;
use Doctrine\ORM\QueryBuilder;

trait ProductAdminQueries
{
    /**
     * @return list<Product>
     */
    public function findAllForAdmin(ProductAdminCriteria $criteria): array
    {
        $qb = $this->createAdminQuery($criteria);

        $qb->setFirstResult(max(0, $criteria->offset));
        $qb->setMaxResults(max(1, min(100, $criteria->limit)));

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(ProductAdminCriteria $criteria): int
    {
        return (int) $this->createAdminQuery($criteria->withoutSortAndPagination())
            ->select('COUNT(p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createAdminQuery(ProductAdminCriteria $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('c', 'b')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.brandReference', 'b');

        if (true === $criteria->onlyFeatured) {
            $qb->andWhere('p.publication.isFeaturedHome = :featured')->setParameter('featured', true);
        }

        if (null !== $criteria->categorySlug && '' !== $criteria->categorySlug) {
            $qb->andWhere('c.slug = :adminCategory')->setParameter('adminCategory', $criteria->categorySlug);
        }

        if (null !== $criteria->sellingType && \in_array($criteria->sellingType, ['sale', 'rental'], true)) {
            if ('sale' === $criteria->sellingType) {
                $qb->andWhere('p.pricing.availableForSale = :adminAvailableForSale')->setParameter('adminAvailableForSale', true);
            } else {
                $qb->andWhere('p.pricing.availableForRental = :adminAvailableForRental')->setParameter('adminAvailableForRental', true);
            }
        }

        if (null !== $criteria->minPriceCents && $criteria->minPriceCents >= 0) {
            $field = 'rental' === $criteria->sellingType ? 'p.pricing.rentalPriceCents' : 'p.pricing.salePriceCents';
            $qb->andWhere(sprintf('%s >= :adminMinPrice', $field))->setParameter('adminMinPrice', $criteria->minPriceCents);
        }

        if (null !== $criteria->maxPriceCents && $criteria->maxPriceCents >= 0) {
            $field = 'rental' === $criteria->sellingType ? 'p.pricing.rentalPriceCents' : 'p.pricing.salePriceCents';
            $qb->andWhere(sprintf('%s <= :adminMaxPrice', $field))->setParameter('adminMaxPrice', $criteria->maxPriceCents);
        }

        if (true === $criteria->lowStockOnly) {
            $qb->andWhere('p.inventory.stock <= p.inventory.lowStockThreshold');
        }

        $this->applySearchFilter($qb, $criteria->search, $criteria->sort, null !== $criteria->sort);
        $this->applyPublishedSort($qb, $criteria->sort, $criteria->search);

        return $qb;
    }
}
