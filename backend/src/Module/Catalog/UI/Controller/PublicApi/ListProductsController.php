<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Module\Catalog\Application\Provider\ProductCatalogSearchProvider;
use App\Module\Catalog\UI\Http\ProductSearchRequestMapper;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products', name: 'api_public_catalog_products_list', methods: ['GET'])]
#[RateLimited('public_api')]
final readonly class ListProductsController
{
    public function __construct(
        private ProductSearchRequestMapper $requests,
        private ProductCatalogSearchProvider $catalog,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success($this->catalog->search($this->requests->map($request)));
    }
}
