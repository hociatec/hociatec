<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Workflow\BrandService;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/brands', name: 'api_admin_catalog_brands_list', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ListBrandsController extends AbstractController
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly ProductRepositoryPort $productRepository,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = Pagination::fromRequest($request, 25, 100);
        $brands = $this->brandService->listForAdmin($pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(
                fn (Brand $brand) => CatalogFormatter::formatBrand(
                    $brand,
                    $this->productRepository->countByBrand($brand)
                ),
                $brands
            ),
            $pagination->metadata($this->brandService->countForAdmin()),
        );
    }
}
