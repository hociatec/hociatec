<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Repository\ProductRepository;

final readonly class ProductQueryService
{
    public function __construct(private ProductRepository $products)
    {
    }

    /**
     * @return list<Product>
     */
    public function listForAdmin(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $lowStockOnly = null,
        ?string $sort = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array
    {
        return $this->products->findAllForAdmin(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $minPriceCents,
            $maxPriceCents,
            $lowStockOnly,
            $sort,
            $limit,
            $offset,
        );
    }

    public function countForAdmin(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $lowStockOnly = null,
    ): int {
        return $this->products->countForAdmin(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $minPriceCents,
            $maxPriceCents,
            $lowStockOnly,
        );
    }

    /**
     * @return list<Product>
     */
    public function listPublished(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
        ?string $sort = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        return $this->products->findPublished(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
            $sort,
            $limit,
            $offset,
        );
    }

    public function countPublished(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): int {
        return $this->products->countPublished(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function collectPublishedFacets(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): array {
        return $this->products->collectPublishedFacets(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
        );
    }

    public function findPublishedBySlug(string $slug): ?Product
    {
        return $this->products->findOnePublishedBySlug($slug);
    }
}
