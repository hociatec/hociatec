<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

final class ProductCatalogVariantSummaryBuilder
{
    public function __construct(
        private readonly ProductCatalogFacetCollector $facetCollector,
        private readonly ProductCatalogModelResolver $modelResolver,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $variants
     *
     * @return array<string, mixed>
     */
    public function build(array $variants): array
    {
        $variantPrices = array_map(
            static fn (array $variant): int => (int) ($variant['priceCents'] ?? 0),
            $variants,
        );
        $variantEffectivePrices = array_map(
            fn (array $variant): int => $this->facetCollector->resolveEffectivePriceCents($variant),
            $variants,
        );

        return [
            'totalStock' => array_reduce(
                $variants,
                static fn (int $total, array $variant): int => $total + (int) ($variant['stock'] ?? 0),
                0,
            ),
            'variantColors' => $this->modelResolver->collectUniqueValues($variants, 'color'),
            'variantStorages' => $this->modelResolver->collectUniqueValues($variants, 'storageCapacity'),
            'variantMemoryRams' => $this->modelResolver->collectUniqueValues($variants, 'memoryRam'),
            'minVariantPriceCents' => $this->nullableMin($variantPrices),
            'maxVariantPriceCents' => $this->nullableMax($variantPrices),
            'minVariantEffectivePriceCents' => $this->nullableMin($variantEffectivePrices),
            'maxVariantEffectivePriceCents' => $this->nullableMax($variantEffectivePrices),
        ];
    }

    /**
     * @param list<int> $values
     */
    private function nullableMin(array $values): ?int
    {
        return [] === $values ? null : min($values);
    }

    /**
     * @param list<int> $values
     */
    private function nullableMax(array $values): ?int
    {
        return [] === $values ? null : max($values);
    }
}
