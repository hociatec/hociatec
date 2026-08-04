<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Catalog\Service\ProductService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/products/{id}', name: 'api_admin_catalog_products_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
class DeleteProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductService $productService,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->productService->delete($product);
        } catch (\Exception) {
            return ApiResponse::internalError('Impossible de supprimer le produit.');
        }

        return ApiResponse::success(['id' => $id], JsonResponse::HTTP_OK, 'Le produit a bien été supprimé du catalogue.');
    }
}
