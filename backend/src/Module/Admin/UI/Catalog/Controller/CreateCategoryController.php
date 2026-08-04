<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Admin\Application\Catalog\DTO\CategoryInput;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\CategoryService;
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

#[Route('/api/admin/catalog/categories', name: 'api_admin_catalog_categories_create', methods: ['POST'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class CreateCategoryController extends AbstractController
{
    public function __construct(private readonly CategoryService $categoryService, private readonly DtoValidator $validator)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = CategoryInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $category = $this->categoryService->create($input->name, $input->slug, $input->description, $input->isVisible);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (CatalogOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::created(CatalogFormatter::formatCategory($category, true), 'La catégorie a bien été créée.');
    }
}
