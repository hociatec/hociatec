<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteStatusTranslator;
use App\Module\Quote\Service\QuoteWorkflowService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}/status', name: 'api_admin_quotes_status', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateQuoteStatusController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly QuoteCalculator $calculator,
        private readonly QuoteWorkflowService $workflow,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $quote = $this->quotes->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $request->toArray();
        $status = QuoteStatusTranslator::toCode((string) ($payload['status'] ?? ''));
        if (!in_array($status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT, Quote::STATUS_ACCEPTED, Quote::STATUS_REFUSED, Quote::STATUS_EXPIRED], true)) {
            return ApiResponse::error('Statut invalide.', Response::HTTP_BAD_REQUEST);
        }

        if (null !== $quote->getConvertedOrder() && Quote::STATUS_ACCEPTED !== $status) {
            return ApiResponse::error('Un devis converti doit rester accepté.', Response::HTTP_BAD_REQUEST);
        }

        $this->workflow->setStatus($quote, $status);

        return ApiResponse::success(QuoteFormatter::formatQuote($quote, $this->calculator));
    }
}
