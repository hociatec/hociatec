<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Service;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class GroupedLowStockCounter
{
    public function __construct(private ProductCatalogRepository $products)
    {
    }

    public function countPublished(int $threshold): int
    {
        $groups = [];

        /** @var Product $product */
        foreach ($this->products->findAllForAdmin() as $product) {
            if (!$product->isPublished()) {
                continue;
            }

            $key = $this->groupKey($product);
            $groups[$key] = ($groups[$key] ?? 0) + $product->getStock();
        }

        return count(array_filter(
            $groups,
            static fn (int $totalStock): bool => $totalStock <= $threshold,
        ));
    }

    private function groupKey(Product $product): string
    {
        $variantGroup = trim((string) ($product->getVariantGroup() ?? ''));
        if ('' !== $variantGroup) {
            return $variantGroup;
        }

        $name = preg_replace('/\s*\([^)]*\)\s*$/u', '', $product->getName()) ?? $product->getName();
        $name = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name) ?? $name;
        $normalizedName = trim($name);

        return '' !== $normalizedName ? $normalizedName : $product->getSku();
    }
}
