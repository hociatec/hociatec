<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\CategoryService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories', name: 'api_admin_catalog_categories_list', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ListCategoriesController extends AbstractController
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $categories = $this->categoryService->listForAdmin();

        return ApiResponse::success([
            'items' => array_map(
                static fn ($category) => CatalogFormatter::formatCategory($category, true),
                $categories
            ),
        ]);
    }
}
