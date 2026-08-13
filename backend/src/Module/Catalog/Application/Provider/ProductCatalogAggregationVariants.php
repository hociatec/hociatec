<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\DTO\ProductCatalogDiscountView;

final class ProductCatalogAggregationVariants
{
    /**
     * @param array<string, mixed>       $lead
     * @param list<array<string, mixed>> $variants
     *
     * @return array<string, mixed>
     */
    public function summarize(array $lead, array $variants): array
    {
        $lead['modelName'] = $this->canonicalProductBaseName($lead);
        $lead['variantsCount'] = count($variants);
        $lead += $this->buildVariantSummary($variants);

        return $lead;
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<list<array<string, mixed>>>
     */
    public function groupProductsByModel(array $products): array
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

        $groups = [];
        foreach ($order as $key) {
            $variants = $grouped[$key];
            usort($variants, $this->compareCanonicalVariant(...));
            $groups[] = $variants;
        }

        return $groups;
    }

    /**
     * @param list<array<string, mixed>> $variants
     *
     * @return array<string, mixed>
     */
    private function buildVariantSummary(array $variants): array
    {
        $variantPrices = array_map(
            static fn (array $variant): int => (int) ($variant['priceCents'] ?? 0),
            $variants,
        );
        $variantEffectivePrices = array_map(
            fn (array $variant): int => $this->resolveEffectivePriceCents($variant),
            $variants,
        );

        return [
            'totalStock' => array_reduce(
                $variants,
                static fn (int $total, array $variant): int => $total + (int) ($variant['stock'] ?? 0),
                0,
            ),
            'variantColors' => $this->collectUniqueValues($variants, 'color'),
            'variantStorages' => $this->collectUniqueValues($variants, 'storageCapacity'),
            'variantMemoryRams' => $this->collectUniqueValues($variants, 'memoryRam'),
            'minVariantPriceCents' => $this->nullableMin($variantPrices),
            'maxVariantPriceCents' => $this->nullableMax($variantPrices),
            'minVariantEffectivePriceCents' => $this->nullableMin($variantEffectivePrices),
            'maxVariantEffectivePriceCents' => $this->nullableMax($variantEffectivePrices),
        ];
    }

    /** @param list<int> $values */
    private function nullableMin(array $values): ?int
    {
        return [] === $values ? null : min($values);
    }

    /** @param list<int> $values */
    private function nullableMax(array $values): ?int
    {
        return [] === $values ? null : max($values);
    }

    /** @param array<string, mixed> $product */
    private function buildGroupKey(array $product): string
    {
        $variantGroup = trim((string) ($product['variantGroup'] ?? ''));
        if ('' !== $variantGroup) {
            return mb_strtolower($variantGroup);
        }

        $baseName = $this->canonicalProductBaseName($product);
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

        uasort($values, static fn (string $left, string $right): int => strcasecmp($left, $right));

        return array_values($values);
    }

    /** @param array<string, mixed> $product */
    private function canonicalProductBaseName(array $product): string
    {
        $name = trim((string) ($product['name'] ?? ''));
        if ('' === $name) {
            return '';
        }

        $normalized = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name) ?? $name;
        $normalized = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($normalized)) ?? $normalized;

        return trim($normalized);
    }

    /** @param array<string, mixed> $product */
    private function resolveEffectivePriceCents(array $product): int
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

    /** @param array<string, mixed> $product */
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
}
