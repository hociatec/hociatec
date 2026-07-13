<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller\PublicApi;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\CategoryService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/categories', name: 'api_public_catalog_categories_list', methods: ['GET'])]
#[RateLimiter('public_api')]
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


