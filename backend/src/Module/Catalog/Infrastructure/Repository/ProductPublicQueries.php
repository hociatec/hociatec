<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Domain\Entity\Product;

trait ProductPublicQueries
{
    /**
     * @return list<Product>
     */
    public function findPublished(ProductCatalogCriteria $criteria): array
    {
        $qb = $this->buildPublishedQuery(
            $criteria,
            true,
        );

        if (null !== $criteria->offset) {
            $qb->setFirstResult(max(0, $criteria->offset));
        }

        if (null !== $criteria->limit) {
            $qb->setMaxResults(max(1, $criteria->limit));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublishedListProjection(ProductCatalogCriteria $criteria): array
    {
        $qb = $this->buildPublishedQuery(
            $criteria,
            true,
        )
            ->select([
                'p.id AS id',
                'p.name AS name',
                'p.slug AS slug',
                'p.sku AS sku',
                'p.shortDescription AS shortDescription',
                'p.description AS description',
                'p.pricing.salePriceCents AS salePriceCents',
                'p.pricing.rentalPriceCents AS rentalPriceCents',
                'p.pricing.availableForSale AS availableForSale',
                'p.pricing.availableForRental AS availableForRental',
                'b.id AS brandId',
                'b.name AS brand',
                'p.characteristics.variantGroup AS variantGroup',
                'p.characteristics.variantPosition AS variantPosition',
                'p.characteristics.releaseYear AS releaseYear',
                'p.characteristics.attributes AS attributes',
                'p.inventory.stock AS stock',
                'p.publication.isPublished AS isPublished',
                'p.publication.isFeaturedHome AS isFeaturedHome',
                'p.imageName AS imageName',
                'p.imageAlt AS imageAlt',
                'p.imageExternalUrl AS imageExternalUrl',
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

        if ('relevance' === $criteria->sort && null !== $criteria->search && '' !== trim($criteria->search)) {
            $this->addRelevanceScoreSelect($qb, mb_strtolower(trim($criteria->search)));
        }

        if (null !== $criteria->offset) {
            $qb->setFirstResult(max(0, $criteria->offset));
        }

        if (null !== $criteria->limit) {
            $qb->setMaxResults(max(1, $criteria->limit));
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        return array_map(fn (array $row): array => $this->appendLegacyAttributeFields($row), $rows);
    }

    public function countPublished(ProductCatalogCriteria $criteria): int
    {
        $qb = $this->buildPublishedQuery(
            $criteria->withoutSortAndPagination(),
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
    public function collectPublishedFacets(ProductCatalogCriteria $criteria): array
    {
        $base = $this->buildPublishedQuery(
            $criteria->withoutSortAndPagination(),
            false,
        );
        $projectedProducts = $this->findPublishedListProjection($criteria->withoutSortAndPagination());

        return [
            'brands' => $this->collectFacetCounts(clone $base, 'b.name', 'brandName'),
            'categories' => $this->collectFacetCounts(clone $base, 'c.name', 'categoryName', 'c.slug'),
            'attributes' => [],
            'storageCapacities' => $this->collectProjectionScalarFacet($projectedProducts, 'storageCapacity'),
            'memoryRams' => $this->collectProjectionScalarFacet($projectedProducts, 'memoryRam'),
            'colors' => $this->collectProjectionScalarFacet($projectedProducts, 'color'),
            'price' => $this->collectPriceBounds(clone $base, $criteria->sellingType),
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

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<array{value:string,count:int}>
     */
    private function collectProjectionScalarFacet(array $products, string $key): array
    {
        $counts = [];

        foreach ($products as $product) {
            $value = trim((string) ($product[$key] ?? ''));
            if ('' === $value) {
                continue;
            }

            $normalized = mb_strtolower($value);
            $counts[$normalized] = [
                'value' => $value,
                'count' => ($counts[$normalized]['count'] ?? 0) + 1,
            ];
        }

        uasort($counts, static fn (array $left, array $right): int => strcasecmp($left['value'], $right['value']));

        return array_values($counts);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function appendLegacyAttributeFields(array $row): array
    {
        $attributes = is_array($row['attributes'] ?? null) ? $row['attributes'] : [];
        $legacy = [
            'storageCapacity' => null,
            'memoryRam' => null,
            'color' => null,
        ];

        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $code = trim((string) ($attribute['code'] ?? ''));
            $value = trim((string) ($attribute['value'] ?? ''));

            if ('' === $value) {
                continue;
            }

            if ('storage' === $code && null === $legacy['storageCapacity']) {
                $legacy['storageCapacity'] = $value;
            } elseif ('ram' === $code && null === $legacy['memoryRam']) {
                $legacy['memoryRam'] = $value;
            } elseif ('color' === $code && null === $legacy['color']) {
                $legacy['color'] = $value;
            }
        }

        return $row + $legacy;
    }
}
