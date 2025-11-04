<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\CategoryRepository;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories/{id}', name: 'api_admin_catalog_categories_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ShowCategoryController extends AbstractController
{
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if ($category === null) {
            return ApiResponse::error('Categorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(CatalogFormatter::formatCategory($category, true));
    }
}


