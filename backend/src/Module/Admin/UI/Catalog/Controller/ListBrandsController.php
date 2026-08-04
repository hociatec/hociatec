<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Service\BrandService;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/brands', name: 'api_admin_catalog_brands_list', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ListBrandsController extends AbstractController
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $brands = $this->brandService->listForAdmin();

        return ApiResponse::success([
            'items' => array_map(
                fn (Brand $brand) => CatalogFormatter::formatBrand(
                    $brand,
                    $this->productRepository->countByBrand($brand)
                ),
                $brands
            ),
        ]);
    }
}
