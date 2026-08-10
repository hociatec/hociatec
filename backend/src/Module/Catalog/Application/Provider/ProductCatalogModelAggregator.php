<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

final class ProductCatalogModelAggregator
{
    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<array<string, mixed>>
     */
    public function aggregate(array $products): array
    {
        $grouped = [];
        $order = [];

        foreach ($products as $product) {
            $key = $this->buildGroupKey($product);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
                $order[] = $key;
            }

            $grouped[$key][] = $product;
        }

        $items = [];

        foreach ($order as $key) {
            $variants = $grouped[$key];
            usort($variants, [$this, 'compareCanonicalVariant']);

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
        $aggregated = $this->aggregate($products);

        return [
            'brands' => $this->countScalarFacet($aggregated, 'brand'),
            'categories' => $this->countCategoryFacet($aggregated),
            'storageCapacities' => $this->countArrayFacet($aggregated, 'variantStorages', 'storageCapacity'),
            'memoryRams' => $this->countArrayFacet($aggregated, 'variantMemoryRams', 'memoryRam'),
            'colors' => $this->countArrayFacet($aggregated, 'variantColors', 'color'),
            'price' => $this->collectPriceBounds($aggregated),
        ];
    }

    /**
     * @param array<string, mixed>        $lead
     * @param list<array<string, mixed>> $variants
     *
     * @return array<string, mixed>
     */
    private function summarizeGroup(array $lead, array $variants): array
    {
        $variantColors = $this->collectUniqueValues($variants, 'color');
        $variantStorages = $this->collectUniqueValues($variants, 'storageCapacity');
        $variantMemoryRams = $this->collectUniqueValues($variants, 'memoryRam');
        $variantPrices = array_map(
            static fn (array $variant): int => (int) ($variant['priceCents'] ?? 0),
            $variants,
        );
        $variantEffectivePrices = array_map(
            fn (array $variant): int => $this->resolveEffectivePriceCents($variant),
            $variants,
        );

        $lead['modelName'] = $this->resolveModelName($lead);
        $lead['variantsCount'] = count($variants);
        $lead['totalStock'] = array_reduce(
            $variants,
            static fn (int $total, array $variant): int => $total + (int) ($variant['stock'] ?? 0),
            0,
        );
        $lead['variantColors'] = $variantColors;
        $lead['variantStorages'] = $variantStorages;
        $lead['variantMemoryRams'] = $variantMemoryRams;
        $lead['minVariantPriceCents'] = [] === $variantPrices ? null : min($variantPrices);
        $lead['maxVariantPriceCents'] = [] === $variantPrices ? null : max($variantPrices);
        $lead['minVariantEffectivePriceCents'] = [] === $variantEffectivePrices ? null : min($variantEffectivePrices);
        $lead['maxVariantEffectivePriceCents'] = [] === $variantEffectivePrices ? null : max($variantEffectivePrices);

        return $lead;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function resolveModelName(array $product): string
    {
        $name = trim((string) ($product['name'] ?? ''));
        if ('' === $name) {
            return '';
        }

        $baseName = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name) ?? $name;
        $baseName = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($baseName)) ?? $baseName;

        return trim($baseName);
    }

    /**
     * @param array<string, mixed> $product
     */
    private function buildGroupKey(array $product): string
    {
        $variantGroup = trim((string) ($product['variantGroup'] ?? ''));
        if ('' !== $variantGroup) {
            return mb_strtolower($variantGroup);
        }

        $name = trim((string) ($product['name'] ?? ''));
        $baseName = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name) ?? $name;
        $baseName = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($baseName)) ?? $baseName;
        $baseName = trim($baseName);

        if ('' !== $baseName) {
            return mb_strtolower($baseName);
        }

        return trim((string) ($product['sku'] ?? ''));
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareCanonicalVariant(array $left, array $right): int
    {
        $leftPosition = isset($left['variantPosition']) ? (int) $left['variantPosition'] : PHP_INT_MAX;
        $rightPosition = isset($right['variantPosition']) ? (int) $right['variantPosition'] : PHP_INT_MAX;

        if ($leftPosition !== $rightPosition) {
            return $leftPosition <=> $rightPosition;
        }

        return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
    }

    /**
     * @param list<array<string, mixed>> $variants
     *
     * @return list<string>
     */
    private function collectUniqueValues(array $variants, string $key): array
    {
        $values = [];

        foreach ($variants as $variant) {
            $value = trim((string) ($variant[$key] ?? ''));

            if ('' === $value) {
                continue;
            }

            $values[mb_strtolower($value)] = $value;
        }

        uasort(
            $values,
            static fn (string $left, string $right): int => strcasecmp($left, $right),
        );

        return array_values($values);
    }

    /**
     * @param array<string, mixed> $product
     */
    private function resolveEffectivePriceCents(array $product): int
    {
        $priceCents = (int) ($product['priceCents'] ?? 0);
        $enabled = (bool) ($product['discountEnabled'] ?? false);
        $type = $product['discountType'] ?? null;
        $value = isset($product['discountValue']) ? (int) $product['discountValue'] : null;
        $startsAt = $product['discountStartsAt'] ?? null;
        $endsAt = $product['discountEndsAt'] ?? null;
        $now = new \DateTimeImmutable();

        if (!$enabled || null === $type || null === $value) {
            return $priceCents;
        }

        if ($startsAt instanceof \DateTimeInterface && $startsAt > $now) {
            return $priceCents;
        }

        if ($endsAt instanceof \DateTimeInterface && $endsAt < $now) {
            return $priceCents;
        }

        if ('fixed_cents' === $type) {
            return max(0, $priceCents - $value);
        }

        if ('percent' === $type) {
            return max(0, (int) round($priceCents * (100 - $value) / 100));
        }

        return $priceCents;
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

            $normalized = mb_strtolower($value);
            $counts[$normalized] = [
                'value' => $value,
                'count' => ($counts[$normalized]['count'] ?? 0) + 1,
                'extra' => null,
            ];
        }

        $items = array_values($counts);
        usort($items, static fn (array $left, array $right): int => strcasecmp($left['value'], $right['value']));

        return $items;
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

            $normalized = mb_strtolower($value);
            $counts[$normalized] = [
                'value' => $value,
                'count' => ($counts[$normalized]['count'] ?? 0) + 1,
                'extra' => $extra,
            ];
        }

        $items = array_values($counts);
        usort($items, static fn (array $left, array $right): int => strcasecmp($left['value'], $right['value']));

        return $items;
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

            foreach ($values as $normalized => $value) {
                $counts[$normalized] = [
                    'value' => $value,
                    'count' => ($counts[$normalized]['count'] ?? 0) + 1,
                    'extra' => null,
                ];
            }
        }

        $items = array_values($counts);
        usort($items, static fn (array $left, array $right): int => strcasecmp($left['value'], $right['value']));

        return $items;
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
}
