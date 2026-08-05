<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Port\QuotePdfRenderer;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/pdf', name: 'api_admin_quotes_generate_pdf', methods: ['POST'])]
#[IsGranted('ROLE_QUOTES_MANAGER')]
class GeneratePdfController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quoteRepository,
        private readonly QuoteCalculator $calculator,
        private readonly QuotePdfRenderer $pdfService,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $totals = $this->calculator->computeTotals($quote);

        try {
            $pdf = $this->pdfService->render($quote, $totals);
        } catch (\RuntimeException) {
            return ApiResponse::error(
                'Génération PDF accessible indisponible.',
                Response::HTTP_NOT_IMPLEMENTED
            );
        }

        return $this->attachments->create($pdf, sprintf('%s.pdf', $quote->getNumber()), 'application/pdf');
    }
}
