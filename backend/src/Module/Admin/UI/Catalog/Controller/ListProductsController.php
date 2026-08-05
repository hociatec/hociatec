<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Admin\Application\Catalog\Mapper\ProductAdminListQueryMapper;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/products', name: 'api_admin_catalog_products_list', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ListProductsController extends AbstractController
{
    public function __construct(
        private readonly ProductQueryService $productService,
        private readonly ProductAdminListQueryMapper $queries,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $query = $this->queries->fromRequest($request);
        $products = $this->productService->listForAdmin(
            categorySlug: $query->categorySlug,
            search: $query->search,
            onlyFeatured: $query->featured,
            sellingType: $query->sellingType,
            minPriceCents: $query->minPriceCents,
            maxPriceCents: $query->maxPriceCents,
            lowStockOnly: $query->lowStock,
            sort: $query->sort,
            limit: $query->perPage,
            offset: $query->offset(),
        );
        $total = $this->productService->countForAdmin(
            categorySlug: $query->categorySlug,
            search: $query->search,
            onlyFeatured: $query->featured,
            sellingType: $query->sellingType,
            minPriceCents: $query->minPriceCents,
            maxPriceCents: $query->maxPriceCents,
            lowStockOnly: $query->lowStock,
        );

        return ApiResponse::paginated(
            array_map(static fn ($product) => CatalogFormatter::formatProduct($product, true), $products),
            [
                'page' => $query->page,
                'perPage' => $query->perPage,
                'total' => $total,
                'totalPages' => max(1, (int) ceil($total / $query->perPage)),
            ],
        );
    }
}
