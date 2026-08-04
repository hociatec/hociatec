<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/products/{id}', name: 'api_admin_catalog_products_show', methods: ['GET'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class ShowProductController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(CatalogFormatter::formatProduct($product, true));
    }
}
