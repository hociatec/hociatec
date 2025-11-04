<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller\PublicApi;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\CategoryService;
use App\Module\Catalog\Service\ProductService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/categories/{slug}', name: 'api_public_catalog_categories_show', methods: ['GET'])]
#[RateLimiter('public_api')]
class ShowCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductService $productService,
    ) {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $category = $this->categoryService->findVisibleBySlug($slug);

        if ($category === null) {
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
