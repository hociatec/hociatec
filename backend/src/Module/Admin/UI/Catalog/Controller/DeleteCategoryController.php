<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Catalog\Application\Service\CategoryService;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Module\Catalog\Infrastructure\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories/{id}', name: 'api_admin_catalog_categories_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class DeleteCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryService $categoryService,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (null === $category) {
            return ApiResponse::error('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->categoryService->delete($category);
        } catch (CatalogOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::success(['id' => $id], JsonResponse::HTTP_OK, 'La catégorie a bien été supprimée.');
    }
}
