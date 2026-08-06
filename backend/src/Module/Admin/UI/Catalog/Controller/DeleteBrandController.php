<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Catalog\Application\Port\BrandRepositoryPort;
use App\Module\Catalog\Application\Workflow\BrandService;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/brands/{id}', name: 'api_admin_catalog_brands_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class DeleteBrandController extends AbstractController
{
    public function __construct(
        private readonly BrandRepositoryPort $brandRepository,
        private readonly BrandService $brandService,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $brand = $this->brandRepository->find($id);

        if (null === $brand) {
            return ApiResponse::error('Marque introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->brandService->delete($brand);
        } catch (CatalogOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::success(['id' => $id], JsonResponse::HTTP_OK, 'La marque a bien été supprimée.');
    }
}
