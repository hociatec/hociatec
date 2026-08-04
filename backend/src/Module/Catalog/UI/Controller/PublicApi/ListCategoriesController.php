<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/categories', name: 'api_public_catalog_categories_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListCategoriesController extends AbstractController
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $categories = $this->categoryService->listVisible();

        return ApiResponse::success([
            'items' => array_map(
                static fn ($category) => CatalogFormatter::formatCategory($category),
                $categories
            ),
        ]);
    }
}
