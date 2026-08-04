<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\Catalog\Exception\ProductFormRequestException;
use App\Module\Admin\Application\Catalog\Service\ProductFormRequestMapper;
use App\Module\Catalog\Application\Service\CatalogFormatter;
use App\Module\Catalog\Application\Service\ProductService;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/products/{id}', name: 'api_admin_catalog_products_update', methods: ['PUT', 'POST'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
final readonly class UpdateProductController
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductFormRequestMapper $forms,
        private ProductService $products,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product instanceof Product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $data = $this->forms->update($request, $product);
            $product = $this->products->update(...$data->updateArguments($product));
        } catch (ProductFormRequestException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException|CatalogOperationException $exception) {
            return ApiResponse::error(
                'Impossible de mettre à jour le produit.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()],
            );
        }

        return ApiResponse::success(CatalogFormatter::formatProduct($product, true));
    }
}
