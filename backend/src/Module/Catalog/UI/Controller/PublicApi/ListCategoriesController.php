<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\CategoryService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/categories', name: 'api_public_catalog_categories_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListCategoriesController extends AbstractController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CatalogFormatter $catalogFormatter,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 25, 100);
        $categories = $this->categoryService->listVisible($pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(
                fn ($category) => $this->catalogFormatter->formatCategory($category),
                $categories
            ),
            $pagination->metadata($this->categoryService->countVisible()),
        );
    }
}
