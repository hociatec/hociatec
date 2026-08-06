<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Application\Query\ProductAdminCriteria;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class GroupedLowStockCounter
{
    private const BATCH_SIZE = 100;

    public function __construct(private ProductCatalogRepository $products)
    {
    }

    public function countPublished(int $threshold): int
    {
        $groups = [];
        $offset = 0;

        do {
            $page = $this->products->findAllForAdmin(new ProductAdminCriteria(limit: self::BATCH_SIZE, offset: $offset));

            /** @var Product $product */
            foreach ($page as $product) {
                if (!$product->isPublished()) {
                    continue;
                }

                $key = $this->groupKey($product);
                $groups[$key] = ($groups[$key] ?? 0) + $product->getStock();
            }

            $offset += self::BATCH_SIZE;
        } while (self::BATCH_SIZE === count($page));

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
