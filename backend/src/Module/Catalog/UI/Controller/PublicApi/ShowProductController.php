<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Service\ProductQueryService;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use App\Module\Rating\Infrastructure\Repository\ProductRatingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products/{slug}', name: 'api_public_catalog_products_show', methods: ['GET'])]
#[RateLimited('public_api')]
class ShowProductController extends AbstractController
{
    public function __construct(
        private readonly ProductQueryService $productService,
        private readonly ProductRatingRepository $ratings,
    ) {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $product = $this->productService->findPublishedBySlug($slug);

        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = CatalogFormatter::formatProduct($product);

        if ($product->getReviewsCount() > 0) {
            $latestReviews = $this->ratings->findPublishedByProduct($product, 3, 0);
            if ([] !== $latestReviews) {
                $data['reviews']['items'] = array_map(
                    static fn ($rating) => ProductReviewFormatter::formatRating($rating),
                    $latestReviews,
                );
            }
        }

        return ApiResponse::success($data);
    }
}
