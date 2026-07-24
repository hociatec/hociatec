<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\BrandRepository;
use App\Module\Catalog\Service\BrandService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/brands/{id}', name: 'api_admin_catalog_brands_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteBrandController extends AbstractController
{
    public function __construct(
        private readonly BrandRepository $brandRepository,
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
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                'Impossible de supprimer la marque.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::success(['id' => $id]);
    }
}
