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
use Throwable;

#[Route('/api/admin/catalog/products/{id}', name: 'api_admin_catalog_products_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
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

        if ($product === null) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->productService->delete($product);
        } catch (Throwable $exception) {
            return ApiResponse::error(
                'Impossible de supprimer le produit.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::success(['id' => $id]);
    }
}

