<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

final class ProductCatalogModelAggregator
{
    private readonly ProductCatalogFacetCollector $facetCollector;
    private readonly ProductCatalogModelResolver $modelResolver;
    private readonly ProductCatalogVariantSummaryBuilder $variantSummaryBuilder;

    public function __construct(
        ?ProductCatalogFacetCollector $facetCollector = null,
        ?ProductCatalogModelResolver $modelResolver = null,
        ?ProductCatalogVariantSummaryBuilder $variantSummaryBuilder = null,
    ) {
        $this->facetCollector = $facetCollector ?? new ProductCatalogFacetCollector();
        $this->modelResolver = $modelResolver ?? new ProductCatalogModelResolver();
        $this->variantSummaryBuilder = $variantSummaryBuilder ?? new ProductCatalogVariantSummaryBuilder(
            $this->facetCollector,
            $this->modelResolver,
        );
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<array<string, mixed>>
     */
    public function aggregate(array $products): array
    {
        $items = [];

        foreach ($this->groupProductsByModel($products) as $variants) {
            $lead = $variants[0] ?? null;
            if (null === $lead) {
                continue;
            }

            $items[] = $this->summarizeGroup($lead, $variants);
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    public function collectFacets(array $products): array
    {
        return $this->facetCollector->collectFacets($this->aggregate($products));
    }

    /**
     * @param array<string, mixed>       $lead
     * @param list<array<string, mixed>> $variants
     *
     * @return array<string, mixed>
     */
    private function summarizeGroup(array $lead, array $variants): array
    {
        $lead['modelName'] = $this->modelResolver->resolveModelName($lead);
        $lead['variantsCount'] = count($variants);
        $lead += $this->variantSummaryBuilder->build($variants);

        return $lead;
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<list<array<string, mixed>>>
     */
    private function groupProductsByModel(array $products): array
    {
        $grouped = [];
        $order = [];

        foreach ($products as $product) {
            $key = $this->modelResolver->buildGroupKey($product);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
                $order[] = $key;
            }

            $grouped[$key][] = $product;
        }

        $groups = [];

        foreach ($order as $key) {
            $variants = $grouped[$key];
            usort($variants, $this->modelResolver->compareCanonicalVariant(...));
            $groups[] = $variants;
        }

        return $groups;
    }
}
