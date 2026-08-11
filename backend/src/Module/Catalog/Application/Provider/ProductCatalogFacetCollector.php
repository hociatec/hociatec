<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\DTO\ProductCatalogDiscountView;
use App\Module\Catalog\Application\DTO\ProductCatalogFacetItem;
use App\Module\Catalog\Application\DTO\ProductCatalogPriceRange;

final class ProductCatalogFacetCollector
{
    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    public function collectFacets(array $products): array
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
     * @param array<string, mixed> $product
     */
    public function resolveEffectivePriceCents(array $product): int
    {
        $priceCents = (int) ($product['priceCents'] ?? 0);
        $discount = $this->extractDiscount($product);

        if (null === $discount || !$this->isDiscountActive($discount->startsAt, $discount->endsAt)) {
            return $priceCents;
        }

        if ('fixed_cents' === $discount->type) {
            return max(0, $priceCents - $discount->value);
        }

        if ('percent' === $discount->type) {
            return max(0, (int) round($priceCents * (100 - $discount->value) / 100));
        }

        return $priceCents;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function extractDiscount(array $product): ?ProductCatalogDiscountView
    {
        $enabled = (bool) ($product['discountEnabled'] ?? false);
        $type = $product['discountType'] ?? null;
        $value = isset($product['discountValue']) ? (int) $product['discountValue'] : null;

        if (!$enabled || !is_string($type) || null === $value) {
            return null;
        }

        return new ProductCatalogDiscountView(
            $type,
            $value,
            $product['discountStartsAt'] instanceof \DateTimeInterface ? $product['discountStartsAt'] : null,
            $product['discountEndsAt'] instanceof \DateTimeInterface ? $product['discountEndsAt'] : null,
        );
    }

    private function isDiscountActive(?\DateTimeInterface $startsAt, ?\DateTimeInterface $endsAt): bool
    {
        $now = new \DateTimeImmutable();

        if ($startsAt instanceof \DateTimeInterface && $startsAt > $now) {
            return false;
        }

        return !$endsAt instanceof \DateTimeInterface || $endsAt >= $now;
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
            fn (array $product): int => isset($product['minVariantEffectivePriceCents'])
                ? (int) $product['minVariantEffectivePriceCents']
                : $this->resolveEffectivePriceCents($product),
            $products,
        );

        return new ProductCatalogPriceRange(min($prices), max($prices));
    }

    /**
     * @param array<string, ProductCatalogFacetItem> $counts
     */
    private function incrementFacetCount(array &$counts, string $value, ?string $extra = null): void
    {
        $normalized = mb_strtolower($value);
        $current = $counts[$normalized] ?? null;
        $counts[$normalized] = new ProductCatalogFacetItem(
            $value,
            ($current?->count ?? 0) + 1,
            $extra,
        );
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
