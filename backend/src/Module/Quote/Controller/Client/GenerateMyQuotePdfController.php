<?php

declare(strict_types=1);

namespace App\Module\Quote\Controller\Client;

use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuotePdfService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me/{id}/pdf', name: 'api_quotes_me_generate_pdf', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class GenerateMyQuotePdfController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteCalculator $calculator,
        private readonly QuotePdfService $pdfService,
    ) {
    }

    public function __invoke(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $email = $user->getEmail();

        $quote = $this->quoteRepository->find($id);
        if (null === $quote || (string) strtolower((string) $quote->getCustomerEmail()) !== strtolower((string) $email)) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $totals = $this->calculator->computeTotals($quote);

        try {
            $pdf = $this->pdfService->render($quote, $totals);
        } catch (\Exception) {
            return ApiResponse::error(
                'Génération PDF accessible indisponible.',
                Response::HTTP_NOT_IMPLEMENTED
            );
        }

        $filename = sprintf('%s.pdf', $quote->getNumber());
        $response = new Response($pdf);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
