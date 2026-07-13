<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\CategoryRepository;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\CategoryService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/api/admin/catalog/categories/{id}', name: 'api_admin_catalog_categories_update', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryService $categoryService,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if ($category === null) {
            return ApiResponse::error('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $description = $payload['description'] ?? null;
        $slugInput = $payload['slug'] ?? null;
        $slug = is_string($slugInput) ? trim($slugInput) : null;
        if ($slug === '') {
            $slug = null;
        }
        $isVisible = $this->normalizeBoolean($payload['isVisible'] ?? true);

        try {
            $category = $this->categoryService->update($category, $name, $slug, $description, $isVisible);
        } catch (Throwable $exception) {
            return ApiResponse::error(
                'Impossible de mettre à jour la catégorie.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::success(CatalogFormatter::formatCategory($category, true));
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);

            return in_array($lower, ['1', 'true', 'on', 'yes'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return (bool) $value;
    }
}

