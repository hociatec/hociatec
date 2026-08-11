<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

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
            'brands' => $this->countScalarFacet($products, 'brand'),
            'categories' => $this->countCategoryFacet($products),
            'storageCapacities' => $this->countArrayFacet($products, 'variantStorages', 'storageCapacity'),
            'memoryRams' => $this->countArrayFacet($products, 'variantMemoryRams', 'memoryRam'),
            'colors' => $this->countArrayFacet($products, 'variantColors', 'color'),
            'price' => $this->collectPriceBounds($products),
        ];
    }

    /**
     * @param array<string, mixed> $product
     */
    public function resolveEffectivePriceCents(array $product): int
    {
        $priceCents = (int) ($product['priceCents'] ?? 0);
        $discount = $this->extractDiscount($product);

        if (null === $discount || !$this->isDiscountActive($discount['startsAt'], $discount['endsAt'])) {
            return $priceCents;
        }

        if ('fixed_cents' === $discount['type']) {
            return max(0, $priceCents - $discount['value']);
        }

        if ('percent' === $discount['type']) {
            return max(0, (int) round($priceCents * (100 - $discount['value']) / 100));
        }

        return $priceCents;
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array{type:string,value:int,startsAt:mixed,endsAt:mixed}|null
     */
    private function extractDiscount(array $product): ?array
    {
        $enabled = (bool) ($product['discountEnabled'] ?? false);
        $type = $product['discountType'] ?? null;
        $value = isset($product['discountValue']) ? (int) $product['discountValue'] : null;

        if (!$enabled || !is_string($type) || null === $value) {
            return null;
        }

        return [
            'type' => $type,
            'value' => $value,
            'startsAt' => $product['discountStartsAt'] ?? null,
            'endsAt' => $product['discountEndsAt'] ?? null,
        ];
    }

    private function isDiscountActive(mixed $startsAt, mixed $endsAt): bool
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
     * @return list<array{value:string,count:int,extra:null}>
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

        return array_map(
            static fn (array $item): array => [
                'value' => $item['value'],
                'count' => $item['count'],
                'extra' => null,
            ],
            $this->sortFacetItems($counts),
        );
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<array{value:string,count:int,extra:?string}>
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
     * @return list<array{value:string,count:int,extra:null}>
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

        return array_map(
            static fn (array $item): array => [
                'value' => $item['value'],
                'count' => $item['count'],
                'extra' => null,
            ],
            $this->sortFacetItems($counts),
        );
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array{min:int|null,max:int|null}
     */
    private function collectPriceBounds(array $products): array
    {
        if ([] === $products) {
            return ['min' => null, 'max' => null];
        }

        $prices = array_map(
            fn (array $product): int => isset($product['minVariantEffectivePriceCents'])
                ? (int) $product['minVariantEffectivePriceCents']
                : $this->resolveEffectivePriceCents($product),
            $products,
        );

        return [
            'min' => min($prices),
            'max' => max($prices),
        ];
    }

    /**
     * @param array<string, array{value:string,count:int,extra:?string}> $counts
     */
    private function incrementFacetCount(array &$counts, string $value, ?string $extra = null): void
    {
        $normalized = mb_strtolower($value);
        $counts[$normalized] = [
            'value' => $value,
            'count' => ($counts[$normalized]['count'] ?? 0) + 1,
            'extra' => $extra,
        ];
    }

    /**
     * @template TExtra of string|null
     *
     * @param array<string, array{value:string,count:int,extra:TExtra}> $counts
     *
     * @return list<array{value:string,count:int,extra:TExtra}>
     */
    private function sortFacetItems(array $counts): array
    {
        $items = array_values($counts);
        usort($items, static fn (array $left, array $right): int => strcasecmp($left['value'], $right['value']));

        return $items;
    }
}
