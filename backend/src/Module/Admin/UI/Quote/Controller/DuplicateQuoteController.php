<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/duplicate', name: 'api_admin_quotes_duplicate', methods: ['POST'])]
#[IsGranted('ROLE_QUOTES_MANAGER')]
class DuplicateQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quoteRepository,
        private readonly QuoteDomainService $quoteService,
        private readonly QuoteFormatter $formatter,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $copy = $this->quoteService->duplicate($quote);

        return ApiResponse::success($this->formatter->formatQuote($copy), JsonResponse::HTTP_OK, 'Le devis a bien été dupliqué.');
    }
}
