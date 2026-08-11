<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Application\Workflow\CategoryCatalogWorkflow;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/categories/{slug}', name: 'api_public_catalog_categories_show', methods: ['GET'])]
#[RateLimited('public_api')]
class ShowCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryCatalogWorkflow $categoryService,
        private readonly ProductQueryService $productService,
        private readonly CatalogFormatter $catalogFormatter,
    ) {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $category = $this->categoryService->findVisibleBySlug($slug);

        if (null === $category) {
            return ApiResponse::error('Categorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        $products = $this->productService->listPublished(new ProductCatalogCriteria([
            'categorySlug' => $slug,
        ]));

        return ApiResponse::success([
            'category' => $this->catalogFormatter->formatCategory($category),
            'products' => array_map(
                fn ($product) => $this->catalogFormatter->formatProduct($product),
                $products
            ),
        ]);
    }
}
