<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteService as QuoteDomainService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/duplicate', name: 'api_admin_quotes_duplicate', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class DuplicateQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteDomainService $quoteService,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $copy = $this->quoteService->duplicate($quote);

        return ApiResponse::success(QuoteFormatter::formatQuote($copy, $this->calculator), JsonResponse::HTTP_OK, 'Le devis a bien été dupliqué.');
    }
}
