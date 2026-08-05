<?php

declare(strict_types=1);

namespace App\Module\Rating\UI\Controller;

use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use App\Module\Rating\Application\Port\ProductRatingRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products/{slug}/reviews', name: 'api_public_catalog_product_reviews', methods: ['GET'])]
#[RateLimited('public_api')]
class ListProductReviewsController extends AbstractController
{
    public function __construct(
        private readonly ProductQueryService $products,
        private readonly ProductRatingRepositoryPort $ratings,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $product = $this->products->findPublishedBySlug($slug);
        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $pagination = RequestQueryMapper::pagination($request, 10, 50);

        $items = $this->ratings->findPublishedByProduct($product, $pagination->perPage, $pagination->offset());
        $formatted = array_map(
            static fn ($rating) => ProductReviewFormatter::formatRating($rating),
            $items,
        );

        return ApiResponse::success([
            'items' => $formatted,
            'meta' => [
                'page' => $pagination->page,
                'perPage' => $pagination->perPage,
                'total' => $product->getReviewsCount(),
                'average' => $product->getReviewsAverage(),
            ],
        ]);
    }
}
