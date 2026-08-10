<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

use App\Module\Catalog\Application\Query\ProductAdminCriteria;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
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

    /** @return list<Product> */
    public function findPublishedByVariantGroupOrdered(string $variantGroup): array;

    public function countByBrand(Brand $brand): int;

    public function clearBrand(Brand $brand): void;

    public function existsWithSku(string $sku, ?int $excludeId = null): bool;

    public function existsWithSlug(string $slug, ?int $excludeId = null): bool;

    public function countLowStock(int $threshold = 3): int;

    /** @return list<Product> */
    public function findLowStock(int $threshold = 3, int $limit = 8): array;

    /** @return list<Product> */
    public function findAllForAdmin(ProductAdminCriteria $criteria): array;

    public function countForAdmin(ProductAdminCriteria $criteria): int;

    /** @return list<Product> */
    public function findPublished(ProductCatalogCriteria $criteria): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublishedListProjection(ProductCatalogCriteria $criteria): array;

    public function countPublished(ProductCatalogCriteria $criteria): int;

    /** @return array<string, mixed> */
    public function collectPublishedFacets(ProductCatalogCriteria $criteria): array;

    public function findOnePublishedBySlug(string $slug): ?Product;
}
