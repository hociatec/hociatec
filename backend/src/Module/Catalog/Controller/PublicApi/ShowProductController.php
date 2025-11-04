<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller\PublicApi;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\ProductService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products/{slug}', name: 'api_public_catalog_products_show', methods: ['GET'])]
#[RateLimiter('public_api')]
class ShowProductController extends AbstractController
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $product = $this->productService->findPublishedBySlug($slug);

        if ($product === null) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(CatalogFormatter::formatProduct($product));
    }
}
