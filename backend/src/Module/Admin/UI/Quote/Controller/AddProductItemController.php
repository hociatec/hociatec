<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Admin\Application\Quote\DTO\QuoteProductItemInput;
use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\DTO\QuoteItemAddition;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
        private readonly QuoteRepositoryPort $quoteRepository,
        private readonly ProductRepositoryPort $productRepository,
        private readonly QuoteCalculator $calculator,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::optionalPayload($request);
        $input = QuoteProductItemInput::fromArray($payload);
        $this->validator->validate($input);
        $product = $this->productRepository->find($input->productId);
        if (null === $product || !$product->isPublished()) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->workflow->addProductItem($quote, $product, QuoteItemAddition::fromArray($input->toPayload()));

        return ApiResponse::success(QuoteFormatter::formatQuote($quote, $this->calculator));
    }
}
