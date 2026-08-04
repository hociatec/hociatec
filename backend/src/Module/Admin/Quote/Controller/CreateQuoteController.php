<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Admin\Quote\DTO\QuotePayloadInput;
use App\Module\Quote\DTO\QuotePayload;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteEmailService;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteService as QuoteDomainService;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $input = QuotePayloadInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $quote = $this->quoteService->createFromPayload(QuotePayload::fromArray($input->toPayload()));
        } catch (\Exception) {
            return ApiResponse::internalError('Impossible de créer le devis.');
        }

        $data = QuoteFormatter::formatQuote($quote, $this->calculator);

        try {
            $data['emailNotificationSent'] = $this->quoteEmailService->sendCreatedIfNeeded($quote);
        } catch (\Exception $exception) {
            $data['emailNotificationSent'] = false;
            $data['emailNotificationError'] = 'Notification email indisponible.';
        }

        return ApiResponse::created($data);
    }
}
