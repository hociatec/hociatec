<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Infrastructure\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories/{id}', name: 'api_admin_catalog_categories_show', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ShowCategoryController extends AbstractController
{
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (null === $category) {
            return ApiResponse::error('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(CatalogFormatter::formatCategory($category, true));
    }
}
