<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/products', name: 'api_admin_catalog_products_list', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ListProductsController extends AbstractController
{
    public function __construct(private readonly ProductQueryService $productService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $hasFilters = [] !== array_intersect(
            array_keys($request->query->all()),
            ['page', 'perPage', 'search', 'category', 'featured', 'sellingType', 'minPrice', 'maxPrice', 'stock', 'sort'],
        );

        if (!$hasFilters) {
            $products = $this->productService->listForAdmin();

            return ApiResponse::success([
                'items' => array_map(
                    static fn ($product) => CatalogFormatter::formatProduct($product, true),
                    $products
                ),
            ]);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = max(1, min(48, $request->query->getInt('perPage', 12)));
        $category = $this->string($request->query->get('category'));
        $search = $this->string($request->query->get('search'));
        $featured = $this->booleanQuery($request, 'featured');
        $lowStock = 'low' === $this->string($request->query->get('stock'));
        $sellingType = $this->choice($request->query->get('sellingType'), ['sale', 'rental']);
        $sort = $this->choice($request->query->get('sort'), [
            'relevance', 'price_asc', 'price_desc', 'release_year_desc', 'release_year_asc',
            'name_desc', 'stock_desc', 'stock_asc', 'created_desc',
        ]);
        $minPrice = $this->price($request->query->get('minPrice'));
        $maxPrice = $this->price($request->query->get('maxPrice'));
        $filters = [$category, $search, $featured, $sellingType, $minPrice, $maxPrice, $lowStock, $sort];
        $products = $this->productService->listForAdmin(...[...$filters, $perPage, ($page - 1) * $perPage]);
        $total = $this->productService->countForAdmin(...array_slice($filters, 0, 7));

        return ApiResponse::paginated(
            array_map(static fn ($product) => CatalogFormatter::formatProduct($product, true), $products),
            [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
            ],
        );
    }

    private function string(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return '' !== $value ? $value : null;
    }

    /** @param list<string> $allowed */
    private function choice(mixed $value, array $allowed): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function booleanQuery(Request $request, string $name): ?bool
    {
        if (!$request->query->has($name)) {
            return null;
        }

        return in_array(strtolower((string) $request->query->get($name)), ['1', 'true', 'yes', 'on'], true);
    }

    private function price(mixed $value): ?int
    {
        if (null === $value || '' === $value || !is_numeric($value)) {
            return null;
        }

        return max(0, (int) round((float) $value * 100));
    }
}
