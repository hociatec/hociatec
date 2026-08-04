<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Product;

interface ProductCatalogRepository
{
    public function findProduct(int $id): ?Product;

    public function findForUpdate(int $id): ?Product;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Product>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @return list<Product> */
    public function findByVariantGroupOrdered(string $variantGroup): array;

    public function countByBrand(Brand $brand): int;

    public function clearBrand(Brand $brand): void;

    public function existsWithSku(string $sku, ?int $excludeId = null): bool;

    public function existsWithSlug(string $slug, ?int $excludeId = null): bool;

    public function countLowStock(int $threshold = 3): int;

    /** @return list<Product> */
    public function findLowStock(int $threshold = 3, int $limit = 8): array;

    /** @return list<Product> */
    public function findAllForAdmin(
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
    ): array;

    public function countForAdmin(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $lowStockOnly = null,
    ): int;

    /** @return list<Product> */
    public function findPublished(
        ?string $categorySlug = null,
        ?string $search = null,
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
    ): array;

    public function countPublished(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): int;

    /** @return array<string, mixed> */
    public function collectPublishedFacets(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): array;

    public function findOnePublishedBySlug(string $slug): ?Product;
}
