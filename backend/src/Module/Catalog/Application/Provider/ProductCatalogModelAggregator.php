<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

final class ProductCatalogModelAggregator
{
    public function __construct(
        private readonly ProductCatalogAggregationVariants $variants = new ProductCatalogAggregationVariants(),
        private readonly ProductCatalogAggregationFacets $facets = new ProductCatalogAggregationFacets(),
    ) {
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<array<string, mixed>>
     */
    public function aggregate(array $products): array
    {
        $items = [];

        foreach ($this->variants->groupProductsByModel($products) as $variants) {
            $lead = $variants[0] ?? null;
            if (null === $lead) {
                continue;
            }

            $items[] = $this->variants->summarize($lead, $variants);
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    public function collectFacets(array $products, array $categoryAttributeDefinitions = [], ?string $selectedCategorySlug = null): array
    {
        return $this->facets->collect($this->aggregate($products), $categoryAttributeDefinitions, $selectedCategorySlug);
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    public function collectRawFacets(array $products, array $categoryAttributeDefinitions = [], ?string $selectedCategorySlug = null): array
    {
        return $this->facets->collect($products, $categoryAttributeDefinitions, $selectedCategorySlug);
    }
}
