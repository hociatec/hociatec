<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

final class ProductCatalogModelResolver
{
    /**
     * @param array<string, mixed> $product
     */
    public function resolveModelName(array $product): string
    {
        return $this->canonicalProductBaseName($product);
    }

    /**
     * @param array<string, mixed> $product
     */
    public function buildGroupKey(array $product): string
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
    public function compareCanonicalVariant(array $left, array $right): int
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
    public function collectUniqueValues(array $variants, string $key): array
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
    private function canonicalProductBaseName(array $product): string
    {
        $name = trim((string) ($product['name'] ?? ''));
        if ('' === $name) {
            return '';
        }

        return $this->stripTrailingParenthesizedSuffix($name);
    }

    private function stripTrailingParenthesizedSuffix(string $value): string
    {
        $normalized = trim($value);
        $normalized = preg_replace('/\s*\([^)]*\)\s*$/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($normalized)) ?? $normalized;

        return trim($normalized);
    }
}
