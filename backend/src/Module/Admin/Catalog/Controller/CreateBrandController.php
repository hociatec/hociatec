<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Admin\Catalog\DTO\CatalogNameInput;
use App\Module\Catalog\Service\BrandService;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/brands', name: 'api_admin_catalog_brands_create', methods: ['POST'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class CreateBrandController extends AbstractController
{
    public function __construct(private readonly BrandService $brandService, private readonly DtoValidator $validator)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (\Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = CatalogNameInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $brand = $this->brandService->create($input->name);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return ApiResponse::internalError('Impossible de créer la marque.');
        }

        return ApiResponse::created(CatalogFormatter::formatBrand($brand), 'La marque a bien été créée.');
    }
}
