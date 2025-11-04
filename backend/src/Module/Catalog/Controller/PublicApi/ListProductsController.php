<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller\PublicApi;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\ProductService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products', name: 'api_public_catalog_products_list', methods: ['GET'])]
#[RateLimiter('public_api')]
class ListProductsController extends AbstractController
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $categorySlug = $request->query->get('category');
        $search = $request->query->get('q');
        $homepageParam = $request->query->get('homepage');
        $sellingTypeParam = $request->query->get('sellingType');
        $onlyFeatured = null;

        if ($homepageParam !== null) {
            $onlyFeatured = $this->normalizeBoolean($homepageParam);
        }

        $products = $this->productService->listPublished(
            $categorySlug !== null ? (string) $categorySlug : null,
            $search !== null ? (string) $search : null,
            $onlyFeatured === true ? true : null,
            $this->normalizeSellingType($sellingTypeParam),
        );

        return ApiResponse::success([
            'items' => array_map(
                static fn ($product) => CatalogFormatter::formatProduct($product),
                $products
            ),
        ]);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);

            return in_array($lower, ['1', 'true', 'on', 'yes'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return (bool) $value;
    }

    private function normalizeSellingType(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = is_string($value) ? strtolower($value) : (string) $value;
        return in_array($v, ['sale', 'rental'], true) ? $v : null;
    }
}
