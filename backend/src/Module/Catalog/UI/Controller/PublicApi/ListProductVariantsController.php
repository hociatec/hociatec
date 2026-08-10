<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products/{slug}/variants', name: 'api_public_catalog_products_variants', methods: ['GET'])]
#[RateLimited('public_api')]
class ListProductVariantsController extends AbstractController
{
    public function __construct(
        private readonly ProductQueryService $productService,
        private readonly CatalogFormatter $catalogFormatter,
    ) {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $product = $this->productService->findPublishedBySlug($slug);

        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $variants = array_map(
            fn ($variant) => $this->catalogFormatter->formatProduct($variant),
            $this->productService->findPublishedVariantsByProduct($product),
        );

        return ApiResponse::success(['items' => $variants]);
    }
}
