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
    public function collect(array $products, array $categoryAttributeDefinitions = [], ?string $selectedCategorySlug = null): array
    {
        $allowedAttributes = $this->resolveAllowedAttributeDefinitions($categoryAttributeDefinitions, $selectedCategorySlug);

        return [
            'brands' => $this->facetItemsToArrays($this->countScalarFacet($products, 'brand')),
            'categories' => $this->facetItemsToArrays($this->countCategoryFacet($products)),
            'attributes' => $this->countAttributeFacets($products, $allowedAttributes),
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
     *
     * @return list<array{code:string,label:string,values:list<array{value:string,count:int,extra:?string}>}>
     */
    private function countAttributeFacets(array $products, array $allowedAttributes): array
    {
        $facets = [];

        foreach ($products as $product) {
            $attributeGroups = $product['variantAttributes'] ?? null;

            if (!is_array($attributeGroups) || [] === $attributeGroups) {
                $attributeGroups = [];

                foreach ($this->normalizeProductAttributes($product) as $attribute) {
                    $attributeGroups[] = [
                        'code' => $attribute['code'],
                        'label' => $attribute['label'],
                        'values' => [$attribute['value']],
                    ];
                }
            }

            foreach ($attributeGroups as $attributeGroup) {
                if (!is_array($attributeGroup)) {
                    continue;
                }

                $code = trim((string) ($attributeGroup['code'] ?? ''));
                $label = trim((string) ($attributeGroup['label'] ?? ''));
                $values = $attributeGroup['values'] ?? null;

                if ('' === $code || '' === $label || !is_array($values)) {
                    continue;
                }

                if (!isset($facets[$code])) {
                    $facets[$code] = [
                        'code' => $code,
                        'label' => $label,
                        'counts' => [],
                    ];
                }

                foreach ($values as $rawValue) {
                    $value = trim((string) $rawValue);
                    if ('' === $value) {
                        continue;
                    }

                    $this->incrementFacetCount($facets[$code]['counts'], $value);
                }
            }
        }

        $formatted = array_map(function (array $facet): array {
            return [
                'code' => $facet['code'],
                'label' => $facet['label'],
                'values' => $this->facetItemsToArrays($this->sortFacetItems($facet['counts'])),
            ];
        }, array_values($facets));

        if ([] !== $allowedAttributes) {
            $formatted = array_values(array_filter(
                $formatted,
                static fn (array $facet): bool => isset($allowedAttributes[$facet['code']]),
            ));

            usort($formatted, static function (array $left, array $right) use ($allowedAttributes): int {
                $leftPosition = $allowedAttributes[$left['code']]['position'] ?? PHP_INT_MAX;
                $rightPosition = $allowedAttributes[$right['code']]['position'] ?? PHP_INT_MAX;

                if ($leftPosition !== $rightPosition) {
                    return $leftPosition <=> $rightPosition;
                }

                return strcasecmp($left['label'], $right['label']);
            });
        } else {
            usort($formatted, static fn (array $left, array $right): int => strcasecmp($left['label'], $right['label']));
        }

        return $formatted;
    }

    /**
     * @param array<string, list<array{code:string,label:string,isRequired:bool,isGlobalFilter:bool}>> $categoryAttributeDefinitions
     *
     * @return array<string, array{code:string,label:string,position:int}>
     */
    private function resolveAllowedAttributeDefinitions(array $categoryAttributeDefinitions, ?string $selectedCategorySlug): array
    {
        $allowed = [];
        $position = 0;

        if (null === $selectedCategorySlug) {
            return [];
        }

        if (null !== $selectedCategorySlug && isset($categoryAttributeDefinitions[$selectedCategorySlug])) {
            foreach ($categoryAttributeDefinitions[$selectedCategorySlug] as $definition) {
                $code = trim((string) ($definition['code'] ?? ''));
                $label = trim((string) ($definition['label'] ?? ''));

                if ('' === $code || '' === $label) {
                    continue;
                }

                $allowed[$code] = [
                    'code' => $code,
                    'label' => $label,
                    'position' => $position++,
                ];
            }

            return $allowed;
        }

        return $allowed;
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
            static fn (array $product): int => (int) ($product['minVariantEffectivePriceCents'] ?? $product['effectivePriceCents'] ?? $product['minVariantPriceCents'] ?? $product['priceCents'] ?? 0),
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

    /**
     * @param array<string, mixed> $product
     *
     * @return list<array{code:string,label:string,value:string}>
     */
    private function normalizeProductAttributes(array $product): array
    {
        $attributes = $product['attributes'] ?? null;

        if (!is_array($attributes)) {
            return [];
        }

        $normalized = [];

        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $code = trim((string) ($attribute['code'] ?? ''));
            $label = trim((string) ($attribute['label'] ?? ''));
            $value = trim((string) ($attribute['value'] ?? ''));

            if ('' === $code || '' === $label || '' === $value) {
                continue;
            }

            $normalized[] = [
                'code' => $code,
                'label' => $label,
                'value' => $value,
            ];
        }

        return $normalized;
    }
}
