<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Domain\Entity\Product;
use Doctrine\ORM\QueryBuilder;

trait ProductAdminQueries
{
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
            $qb->andWhere('p.publication.isFeaturedHome = :featured')->setParameter('featured', true);
        }

        if (null !== $categorySlug && '' !== $categorySlug) {
            $qb->andWhere('c.slug = :adminCategory')->setParameter('adminCategory', $categorySlug);
        }

        if (null !== $sellingType && \in_array($sellingType, ['sale', 'rental'], true)) {
            $qb->andWhere('p.pricing.sellingType = :adminSellingType')->setParameter('adminSellingType', $sellingType);
        }

        if (null !== $minPriceCents && $minPriceCents >= 0) {
            $qb->andWhere('p.pricing.priceCents >= :adminMinPrice')->setParameter('adminMinPrice', $minPriceCents);
        }

        if (null !== $maxPriceCents && $maxPriceCents >= 0) {
            $qb->andWhere('p.pricing.priceCents <= :adminMaxPrice')->setParameter('adminMaxPrice', $maxPriceCents);
        }

        if (true === $lowStockOnly) {
            $qb->andWhere('p.inventory.stock <= p.inventory.lowStockThreshold');
        }

        $this->applySearchFilter($qb, $search, $sort, null !== $sort);
        $this->applyPublishedSort($qb, $sort, $search);

        return $qb;
    }
}
