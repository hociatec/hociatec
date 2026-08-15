<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\CategoryCatalogWorkflow;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories', name: 'api_admin_catalog_categories_list', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ListCategoriesController extends AbstractController
{
    public function __construct(
        private readonly CategoryCatalogWorkflow $categoryService,
        private readonly CatalogFormatter $catalogFormatter,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $search = RequestQueryMapper::string($request, 'q');
        $categories = $this->categoryService->listForAdmin($pagination->perPage, $pagination->offset(), $search);
        $counts = $this->categoryService->countProductsByCategoryIds(array_values(array_filter(array_map(static fn ($category): ?int => $category->getId(), $categories))));

        return ApiResponse::paginated(
            array_map(
                fn ($category) => $this->catalogFormatter->formatCategory($category, $counts[$category->getId() ?? 0] ?? 0),
                $categories
            ),
            $pagination->metadata($this->categoryService->countForAdmin($search)),
        );
    }
}
