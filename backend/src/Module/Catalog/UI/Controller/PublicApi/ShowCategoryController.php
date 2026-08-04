<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Module\Catalog\Application\Service\CatalogFormatter;
use App\Module\Catalog\Application\Service\CategoryService;
use App\Module\Catalog\Application\Service\ProductQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/categories/{slug}', name: 'api_public_catalog_categories_show', methods: ['GET'])]
#[RateLimited('public_api')]
class ShowCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductQueryService $productService,
    ) {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $category = $this->categoryService->findVisibleBySlug($slug);

        if (null === $category) {
            return ApiResponse::error('Categorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        $products = $this->productService->listPublished($slug, null);

        return ApiResponse::success([
            'category' => CatalogFormatter::formatCategory($category),
            'products' => array_map(
                static fn ($product) => CatalogFormatter::formatProduct($product),
                $products
            ),
        ]);
    }
}
