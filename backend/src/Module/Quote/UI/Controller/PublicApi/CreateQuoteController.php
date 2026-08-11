<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\PublicApi;

use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/quotes', name: 'api_public_quotes_create', methods: ['POST'])]
#[RateLimited('public_api')]
class CreateQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteDomainService $quoteService,
        private readonly QuoteFormatter $formatter,
        private readonly DtoValidator $validator,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.public_api')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        // Force status to sent for public submissions
        $payload['status'] = Quote::STATUS_SENT;
        // Le client ne peut pas modifier les frais de port
        $payload['shippingCents'] = 0;
        $quotePayload = QuotePayload::fromArray($payload);
        $this->validator->validate($quotePayload, message: 'Demande de devis invalide.');

        $customerEmail = is_string($quotePayload->customer['email'] ?? null) ? $quotePayload->customer['email'] : null;
        $limit = $this->limiter->create($this->rateLimitKeys->forRequest($request, $customerEmail))->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error('Trop de demandes de devis. Veuillez réessayer plus tard.', JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $quote = $this->quoteService->createFromPayload($quotePayload);
        } catch (\InvalidArgumentException|QuoteOperationException|\RuntimeException $exception) {
            return ApiProblemResponse::fromThrowable($exception);
        }

        return ApiResponse::created($this->formatter->formatQuote($quote), 'Votre devis a bien été enregistré.');
    }
}
