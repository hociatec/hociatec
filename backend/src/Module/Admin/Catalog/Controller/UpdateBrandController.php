<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\BrandRepository;
use App\Module\Catalog\Service\BrandService;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/api/admin/catalog/brands/{id}', name: 'api_admin_catalog_brands_update', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateBrandController extends AbstractController
{
    public function __construct(
        private readonly BrandRepository $brandRepository,
        private readonly BrandService $brandService,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $brand = $this->brandRepository->find($id);

        if ($brand === null) {
            return ApiResponse::error('Marque introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));

        try {
            $brand = $this->brandService->update($brand, $name);
        } catch (Throwable $exception) {
            return ApiResponse::error(
                'Impossible de mettre à jour la marque.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::success(CatalogFormatter::formatBrand($brand));
    }
}
