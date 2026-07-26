<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\BrandRepository;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/brands/{id}', name: 'api_admin_catalog_brands_show', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ShowBrandController extends AbstractController
{
    public function __construct(private readonly BrandRepository $brandRepository)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $brand = $this->brandRepository->find($id);

        if (null === $brand) {
            return ApiResponse::error('Marque introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(CatalogFormatter::formatBrand($brand));
    }
}
