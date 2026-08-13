<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\DTO\ProductCatalogFacetItem;
use App\Module\Catalog\Application\DTO\ProductCatalogPriceRange;

final class ProductCatalogAggregationFacets
{
    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    public function collect(array $products): array
    {
        return [
            'brands' => $this->facetItemsToArrays($this->countScalarFacet($products, 'brand')),
            'categories' => $this->facetItemsToArrays($this->countCategoryFacet($products)),
            'storageCapacities' => $this->facetItemsToArrays($this->countArrayFacet($products, 'variantStorages', 'storageCapacity')),
            'memoryRams' => $this->facetItemsToArrays($this->countArrayFacet($products, 'variantMemoryRams', 'memoryRam')),
            'colors' => $this->facetItemsToArrays($this->countArrayFacet($products, 'variantColors', 'color')),
            'price' => $this->collectPriceBounds($products)->toArray(),
        ];
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<ProductCatalogFacetItem>
     */
    private function countScalarFacet(array $products, string $key): array
    {
        $counts = [];

        foreach ($products as $product) {
            $value = trim((string) ($product[$key] ?? ''));
            if ('' === $value) {
                continue;
            }

            $this->incrementFacetCount($counts, $value);
        }

        return $this->sortFacetItems($counts);
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<ProductCatalogFacetItem>
     */
    private function countCategoryFacet(array $products): array
    {
        $counts = [];

        foreach ($products as $product) {
            $category = $product['category'] ?? null;
            $value = is_array($category)
                ? trim((string) ($category['name'] ?? ''))
                : trim((string) ($product['categoryName'] ?? ''));
            $extra = is_array($category)
                ? (trim((string) ($category['slug'] ?? '')) ?: null)
                : (trim((string) ($product['categorySlug'] ?? '')) ?: null);

            if ('' === $value) {
                continue;
            }

            $this->incrementFacetCount($counts, $value, $extra);
        }

        return $this->sortFacetItems($counts);
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<ProductCatalogFacetItem>
     */
    private function countArrayFacet(array $products, string $arrayKey, string $fallbackKey): array
    {
        $counts = [];

        foreach ($products as $product) {
            $values = [];
            $rawValues = $product[$arrayKey] ?? null;

            if (is_array($rawValues)) {
                foreach ($rawValues as $rawValue) {
                    $value = trim((string) $rawValue);
                    if ('' !== $value) {
                        $values[mb_strtolower($value)] = $value;
                    }
                }
            }

            if ([] === $values) {
                $fallbackValue = trim((string) ($product[$fallbackKey] ?? ''));
                if ('' !== $fallbackValue) {
                    $values[mb_strtolower($fallbackValue)] = $fallbackValue;
                }
            }

            foreach ($values as $value) {
                $this->incrementFacetCount($counts, $value);
            }
        }

        return $this->sortFacetItems($counts);
    }

    /**
     * @param list<array<string, mixed>> $products
     */
    private function collectPriceBounds(array $products): ProductCatalogPriceRange
    {
        if ([] === $products) {
            return new ProductCatalogPriceRange(null, null);
        }

        $prices = array_map(
            static fn (array $product): int => (int) ($product['minVariantEffectivePriceCents'] ?? $product['priceCents'] ?? 0),
            $products,
        );

        return new ProductCatalogPriceRange(min($prices), max($prices));
    }

    /** @param array<string, ProductCatalogFacetItem> $counts */
    private function incrementFacetCount(array &$counts, string $value, ?string $extra = null): void
    {
        $normalized = mb_strtolower($value);
        $current = $counts[$normalized] ?? null;
        $counts[$normalized] = new ProductCatalogFacetItem($value, ($current->count ?? 0) + 1, $extra);
    }

    /**
     * @param array<string, ProductCatalogFacetItem> $counts
     *
     * @return list<ProductCatalogFacetItem>
     */
    private function sortFacetItems(array $counts): array
    {
        $items = array_values($counts);
        usort($items, static fn (ProductCatalogFacetItem $left, ProductCatalogFacetItem $right): int => strcasecmp($left->value, $right->value));

        return $items;
    }

    /**
     * @param list<ProductCatalogFacetItem> $items
     *
     * @return list<array{value:string,count:int,extra:?string}>
     */
    private function facetItemsToArrays(array $items): array
    {
        return array_map(static fn (ProductCatalogFacetItem $item): array => $item->toArray(), $items);
    }
}
