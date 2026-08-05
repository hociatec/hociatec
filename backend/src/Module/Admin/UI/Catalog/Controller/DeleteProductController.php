<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Module\Catalog\Application\Handler\ProductWriteHandler;
use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
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
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductWriteHandler $products,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->products->delete($product);
        } catch (CatalogOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::success(['id' => $id], JsonResponse::HTTP_OK, 'Le produit a bien été supprimé du catalogue.');
    }
}
