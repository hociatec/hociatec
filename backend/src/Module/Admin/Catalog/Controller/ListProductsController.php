<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\ProductQueryService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/products', name: 'api_admin_catalog_products_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListProductsController extends AbstractController
{
    public function __construct(private readonly ProductQueryService $productService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $products = $this->productService->listForAdmin();

        return ApiResponse::success([
            'items' => array_map(
                static fn ($product) => CatalogFormatter::formatProduct($product, true),
                $products
            ),
        ]);
    }
}
