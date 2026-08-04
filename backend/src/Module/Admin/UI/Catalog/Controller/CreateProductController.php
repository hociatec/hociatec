<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\Catalog\Exception\ProductFormRequestException;
use App\Module\Admin\Application\Catalog\Service\ProductFormRequestMapper;
use App\Module\Catalog\Application\Service\CatalogFormatter;
use App\Module\Catalog\Application\Service\ProductService;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog/products', name: 'api_admin_catalog_products_create', methods: ['POST'])]
#[IsGranted('ROLE_CATALOG_MANAGER')]
final readonly class CreateProductController
{
    public function __construct(
        private ProductFormRequestMapper $forms,
        private ProductService $products,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $this->forms->create($request);
            $product = $this->products->create(...$data->createArguments());
        } catch (ProductFormRequestException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException|CatalogOperationException $exception) {
            return ApiResponse::error(
                'Impossible de créer le produit.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()],
            );
        }

        return ApiResponse::created(CatalogFormatter::formatProduct($product, true));
    }
}
