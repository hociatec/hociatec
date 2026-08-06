<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}', name: 'api_admin_quotes_show', methods: ['GET'])]
#[IsGranted('ROLE_QUOTES_MANAGER')]
class ShowQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quoteRepository,
        private readonly QuoteFormatter $formatter,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($this->formatter->formatQuote($quote));
    }
}
