<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\PublicApi;

use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
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
        private readonly QuoteFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        // Force status to sent for public submissions
        $payload['status'] = Quote::STATUS_SENT;
        // Le client ne peut pas modifier les frais de port
        $payload['shippingCents'] = 0;

        try {
            $quote = $this->quoteService->createFromPayload(QuotePayload::fromArray($payload));
        } catch (\InvalidArgumentException|QuoteOperationException|\RuntimeException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::created($this->formatter->formatQuote($quote), 'Votre devis a bien été enregistré.');
    }
}
