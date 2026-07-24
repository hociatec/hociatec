<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteEmailService;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteService as QuoteDomainService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes', name: 'api_admin_quotes_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class CreateQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteDomainService $quoteService,
        private readonly QuoteCalculator $calculator,
        private readonly QuoteEmailService $quoteEmailService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->toArray();

        try {
            $quote = $this->quoteService->createFromPayload($payload);
        } catch (\Throwable $e) {
            return ApiResponse::error('Impossible de creer le devis.', Response::HTTP_BAD_REQUEST, [$e->getMessage()]);
        }

        $data = QuoteFormatter::formatQuote($quote, $this->calculator);

        try {
            $data['emailNotificationSent'] = $this->quoteEmailService->sendCreatedIfNeeded($quote);
        } catch (\Throwable $exception) {
            $data['emailNotificationSent'] = false;
            $data['emailNotificationError'] = $exception->getMessage();
        }

        return ApiResponse::created($data);
    }
}
