<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\AttachmentResponseFactory;
use App\Module\Quote\Application\Service\QuoteCalculator;
use App\Module\Quote\Infrastructure\Pdf\QuotePdfService;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/pdf', name: 'api_admin_quotes_generate_pdf', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class GeneratePdfController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteCalculator $calculator,
        private readonly QuotePdfService $pdfService,
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
