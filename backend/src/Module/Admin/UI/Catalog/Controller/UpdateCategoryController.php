<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Admin\Application\Catalog\DTO\CategoryInput;
use App\Module\Catalog\Application\Port\CategoryRepositoryPort;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\CategoryCatalogWorkflow;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/categories/{id}', name: 'api_admin_catalog_categories_update', methods: ['PUT'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class UpdateCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepositoryPort $categoryRepository,
        private readonly CategoryCatalogWorkflow $categoryService,
        private readonly DtoValidator $validator,
        private readonly CatalogFormatter $catalogFormatter,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (null === $category) {
            return ApiResponse::error('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = CategoryInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $category = $this->categoryService->update($category, $input->name, $input->slug, $input->description, $input->isVisible);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (CatalogOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::success($this->catalogFormatter->formatCategory($category, true), JsonResponse::HTTP_OK, 'La catégorie a bien été mise à jour.');
    }
}
