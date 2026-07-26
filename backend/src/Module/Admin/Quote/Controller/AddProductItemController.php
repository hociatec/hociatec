<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteWorkflowService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/items/product', name: 'api_admin_quotes_add_product_item', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class AddProductItemController extends AbstractController
{
    public function __construct(
        private readonly QuoteWorkflowService $workflow,
        private readonly QuoteRepository $quoteRepository,
        private readonly ProductRepository $productRepository,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = '' !== $request->getContent() ? $request->toArray() : [];
        if (!isset($payload['productId'])) {
            return ApiResponse::error('Produit manquant.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $productId = (int) $payload['productId'];
        $product = $this->productRepository->find($productId);
        if (null === $product || !$product->isPublished()) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->workflow->addProductItem($quote, $product, $payload);

        return ApiResponse::success(QuoteFormatter::formatQuote($quote, $this->calculator));
    }

}
