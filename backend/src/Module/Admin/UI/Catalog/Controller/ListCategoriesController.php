<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\CategoryService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories', name: 'api_admin_catalog_categories_list', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ListCategoriesController extends AbstractController
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = Pagination::fromRequest($request, 25, 100);
        $categories = $this->categoryService->listForAdmin($pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(
                static fn ($category) => CatalogFormatter::formatCategory($category, true),
                $categories
            ),
            $pagination->metadata($this->categoryService->countForAdmin()),
        );
    }
}
