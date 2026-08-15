<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\DTO\ProductCatalogDiscountView;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;
use App\Module\Catalog\Domain\Entity\ProductSellingType;

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
            fn (array $variant): int => $this->resolvePriceCents($variant),
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
            'variantAttributes' => $this->collectVariantAttributes($variants),
            'variantColors' => $this->collectUniqueAttributeValues($variants, 'color'),
            'variantStorages' => $this->collectUniqueAttributeValues($variants, 'storage'),
            'variantMemoryRams' => $this->collectUniqueAttributeValues($variants, 'ram'),
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
    private function collectUniqueAttributeValues(array $variants, string $attributeCode): array
    {
        $values = [];

        foreach ($variants as $variant) {
            foreach ($this->normalizeAttributes($variant['attributes'] ?? null, $variant) as $attribute) {
                if ($attribute['code'] !== $attributeCode) {
                    continue;
                }

                $values[mb_strtolower($attribute['value'])] = $attribute['value'];
            }
        }

        uasort($values, static fn (string $left, string $right): int => strcasecmp($left, $right));

        return array_values($values);
    }

    /**
     * @param list<array<string, mixed>> $variants
     *
     * @return list<array{code:string,label:string,values:list<string>}>
     */
    private function collectVariantAttributes(array $variants): array
    {
        $collected = [];

        foreach ($variants as $variant) {
            foreach ($this->normalizeAttributes($variant['attributes'] ?? null, $variant) as $attribute) {
                $code = $attribute['code'];
                $valueKey = mb_strtolower($attribute['value']);

                if (!isset($collected[$code])) {
                    $collected[$code] = [
                        'code' => $code,
                        'label' => $attribute['label'],
                        'values' => [],
                    ];
                }

                $collected[$code]['values'][$valueKey] = $attribute['value'];
            }
        }

        foreach ($collected as &$attribute) {
            $values = array_values($attribute['values']);
            usort($values, static fn (string $left, string $right): int => strcasecmp($left, $right));
            $attribute['values'] = $values;
        }
        unset($attribute);

        uasort($collected, static fn (array $left, array $right): int => strcasecmp($left['label'], $right['label']));

        return array_values($collected);
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
        $priceCents = $this->resolvePriceCents($product);
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

    /** @param array<string, mixed> $product */
    private function resolvePriceCents(array $product): int
    {
        $sellingType = $this->resolveSellingType($product);
        $field = ProductSellingType::Rental->value === $sellingType ? 'rentalPriceCents' : 'salePriceCents';
        $value = $product[$field] ?? null;

        if (null !== $value) {
            return (int) $value;
        }

        return isset($product['priceCents']) ? (int) $product['priceCents'] : 0;
    }

    /** @param array<string, mixed> $product */
    private function resolveSellingType(array $product): string
    {
        if ($this->supportsSellingType($product, ProductSellingType::Sale->value)) {
            return ProductSellingType::Sale->value;
        }

        if ($this->supportsSellingType($product, ProductSellingType::Rental->value)) {
            return ProductSellingType::Rental->value;
        }

        return ProductSellingType::Sale->value;
    }

    /** @param array<string, mixed> $product */
    private function supportsSellingType(array $product, string $sellingType): bool
    {
        $availabilityField = ProductSellingType::Sale->value === $sellingType ? 'availableForSale' : 'availableForRental';
        if (array_key_exists($availabilityField, $product)) {
            return (bool) $product[$availabilityField];
        }

        $explicitSellingType = ProductSellingType::tryFrom((string) ($product['sellingType'] ?? ''));
        if ($explicitSellingType instanceof ProductSellingType) {
            return $explicitSellingType->value === $sellingType;
        }

        $priceField = ProductSellingType::Rental->value === $sellingType ? 'rentalPriceCents' : 'salePriceCents';
        if (null !== ($product[$priceField] ?? null)) {
            return true;
        }

        if (isset($product['priceCents'])) {
            return ProductSellingType::Sale->value === $sellingType;
        }

        return match ($sellingType) {
            ProductSellingType::Sale->value => false,
            ProductSellingType::Rental->value => false,
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return list<array{code:string,label:string,value:string}>
     */
    private function normalizeAttributes(mixed $attributes, array $product): array
    {
        $normalized = [];

        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $code = isset($attribute['code']) ? trim((string) $attribute['code']) : '';
                $label = isset($attribute['label']) ? trim((string) $attribute['label']) : '';
                $value = isset($attribute['value']) ? trim((string) $attribute['value']) : '';

                if ('' === $code || '' === $label || '' === $value) {
                    continue;
                }

                $normalized[$code] = [
                    'code' => $code,
                    'label' => $label,
                    'value' => $value,
                ];
            }
        }

        foreach ([
            LegacyProductAttribute::fromValue(LegacyProductAttribute::STORAGE_CODE, isset($product['storageCapacity']) ? (string) $product['storageCapacity'] : null),
            LegacyProductAttribute::fromValue(LegacyProductAttribute::MEMORY_RAM_CODE, isset($product['memoryRam']) ? (string) $product['memoryRam'] : null),
            LegacyProductAttribute::fromValue(LegacyProductAttribute::COLOR_CODE, isset($product['color']) ? (string) $product['color'] : null),
        ] as $attribute) {
            if (null === $attribute) {
                continue;
            }

            $normalized[$attribute['code']] = $attribute;
        }

        return array_values($normalized);
    }
}
