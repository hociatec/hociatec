<?php

declare(strict_types=1);

namespace App\Module\Quote\Controller\PublicApi;

use App\Module\Quote\DTO\QuotePayload;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteService as QuoteDomainService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/quotes', name: 'api_public_quotes_create', methods: ['POST'])]
#[RateLimited('public_api')]
class CreateQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteDomainService $quoteService,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Http\JsonPayload::decode($request);
        // Force status to sent for public submissions
        $payload['status'] = Quote::STATUS_SENT;
        // Le client ne peut pas modifier les frais de port
        $payload['shippingCents'] = 0;

        try {
            $quote = $this->quoteService->createFromPayload(QuotePayload::fromArray($payload));
        } catch (\Exception) {
            return ApiResponse::internalError('Impossible de créer le devis.');
        }

        return ApiResponse::created(QuoteFormatter::formatQuote($quote, $this->calculator), 'Votre devis a bien été enregistré.');
    }
}
