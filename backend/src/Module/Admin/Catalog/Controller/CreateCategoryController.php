<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\CategoryService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories', name: 'api_admin_catalog_categories_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class CreateCategoryController extends AbstractController
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $description = $payload['description'] ?? null;
        $slugInput = $payload['slug'] ?? null;
        $slug = is_string($slugInput) ? trim($slugInput) : null;
        if ('' === $slug) {
            $slug = null;
        }
        $isVisible = $this->normalizeBoolean($payload['isVisible'] ?? true);

        try {
            $category = $this->categoryService->create($name, $slug, $description, $isVisible);
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                'Impossible de créer la catégorie.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::created(CatalogFormatter::formatCategory($category, true));
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
            return 1 === $value;
        }

        return (bool) $value;
    }
}
