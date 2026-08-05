<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Application\Query\ProductAdminCriteria;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductQueryService
{
    public function __construct(private ProductCatalogRepository $products)
    {
    }

    /**
     * @return list<Product>
     */
    public function listForAdmin(ProductAdminCriteria $criteria): array
    {
        return $this->products->findAllForAdmin($criteria);
    }

    public function countForAdmin(ProductAdminCriteria $criteria): int
    {
        return $this->products->countForAdmin($criteria);
    }

    /**
     * @return list<Product>
     */
    public function listPublished(ProductCatalogCriteria $criteria): array
    {
        return $this->products->findPublished($criteria);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublishedProjection(ProductCatalogCriteria $criteria): array
    {
        return $this->products->findPublishedListProjection($criteria);
    }

    public function countPublished(ProductCatalogCriteria $criteria): int
    {
        return $this->products->countPublished($criteria);
    }

    /**
     * @return array<string, mixed>
     */
    public function collectPublishedFacets(ProductCatalogCriteria $criteria): array
    {
        return $this->products->collectPublishedFacets($criteria);
    }

    public function findPublishedBySlug(string $slug): ?Product
    {
        return $this->products->findOnePublishedBySlug($slug);
    }
}
