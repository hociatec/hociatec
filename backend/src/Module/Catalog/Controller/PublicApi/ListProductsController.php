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
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(48, (int) $request->query->get('perPage', 12)));
        $offset = ($page - 1) * $perPage;
        $categorySlug = $request->query->get('category');
        $search = $request->query->get('q');
        $homepageParam = $request->query->get('homepage');
        $sellingTypeParam = $request->query->get('sellingType');
        $brandParam = $request->query->get('brand');
        $storageCapacityParam = $request->query->get('storageCapacity');
        $memoryRamParam = $request->query->get('memoryRam');
        $colorParam = $request->query->get('color');
        $minPriceParam = $request->query->get('minPrice');
        $maxPriceParam = $request->query->get('maxPrice');
        $inStockParam = $request->query->get('inStock');
        $sortParam = $request->query->get('sort');
        $onlyFeatured = null;

        if ($homepageParam !== null) {
            $onlyFeatured = $this->normalizeBoolean($homepageParam);
        }

        $normalizedSellingType = $this->normalizeSellingType($sellingTypeParam);
        $normalizedBrand = $brandParam !== null ? trim((string) $brandParam) : null;
        $normalizedStorage = $storageCapacityParam !== null ? trim((string) $storageCapacityParam) : null;
        $normalizedMemory = $memoryRamParam !== null ? trim((string) $memoryRamParam) : null;
        $normalizedColor = $colorParam !== null ? trim((string) $colorParam) : null;
        $normalizedMinPrice = $this->normalizePriceToCents($minPriceParam);
        $normalizedMaxPrice = $this->normalizePriceToCents($maxPriceParam);
        $normalizedInStock = $inStockParam !== null ? $this->normalizeBoolean($inStockParam) : null;
        $normalizedSort = $this->normalizeSort($sortParam);

        $products = $this->productService->listPublished(
            $categorySlug !== null ? (string) $categorySlug : null,
            $search !== null ? (string) $search : null,
            $onlyFeatured === true ? true : null,
            $normalizedSellingType,
            $normalizedBrand,
            $normalizedStorage,
            $normalizedMemory,
            $normalizedColor,
            $normalizedMinPrice,
            $normalizedMaxPrice,
            $normalizedInStock,
            $normalizedSort,
            $perPage,
            $offset,
        );

        $total = $this->productService->countPublished(
            $categorySlug !== null ? (string) $categorySlug : null,
            $search !== null ? (string) $search : null,
            $onlyFeatured === true ? true : null,
            $normalizedSellingType,
            $normalizedBrand,
            $normalizedStorage,
            $normalizedMemory,
            $normalizedColor,
            $normalizedMinPrice,
            $normalizedMaxPrice,
            $normalizedInStock,
        );

        $facets = $this->productService->collectPublishedFacets(
            $categorySlug !== null ? (string) $categorySlug : null,
            $search !== null ? (string) $search : null,
            $onlyFeatured === true ? true : null,
            $normalizedSellingType,
            $normalizedBrand,
            $normalizedStorage,
            $normalizedMemory,
            $normalizedColor,
            $normalizedMinPrice,
            $normalizedMaxPrice,
            $normalizedInStock,
        );

        return ApiResponse::success([
            'items' => array_map(
                static fn ($product) => CatalogFormatter::formatProduct($product),
                $products
            ),
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
            ],
            'facets' => $facets,
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

    private function normalizeSort(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = is_string($value) ? strtolower($value) : (string) $value;

        return in_array($v, ['relevance', 'price_asc', 'price_desc', 'release_year_desc', 'release_year_asc', 'name_desc', 'stock_desc', 'stock_asc', 'created_desc'], true)
            ? $v
            : null;
    }

    private function normalizePriceToCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return max(0, $value * 100);
        }

        if (is_float($value)) {
            return max(0, (int) round($value * 100));
        }

        if (is_string($value)) {
            $normalized = str_replace(',', '.', trim($value));
            if ($normalized === '' || !is_numeric($normalized)) {
                return null;
            }

            return max(0, (int) round(((float) $normalized) * 100));
        }

        return null;
    }
}
