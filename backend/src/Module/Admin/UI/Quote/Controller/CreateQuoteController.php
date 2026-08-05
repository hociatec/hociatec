<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Admin\Application\Quote\DTO\QuotePayloadInput;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Workflow\QuoteEmailService;
use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes', name: 'api_admin_quotes_create', methods: ['POST'])]
#[IsGranted('ROLE_QUOTES_MANAGER')]
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
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $input = QuotePayloadInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $quote = $this->quoteService->createFromPayload(QuotePayload::fromArray($input->toPayload()));
        } catch (\InvalidArgumentException|QuoteOperationException|\RuntimeException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        $data = QuoteFormatter::formatQuote($quote, $this->calculator);

        try {
            $data['emailNotificationSent'] = $this->quoteEmailService->sendCreatedIfNeeded($quote);
        } catch (\RuntimeException $exception) {
            $data['emailNotificationSent'] = false;
            $data['emailNotificationError'] = 'Notification email indisponible.';
        }

        return ApiResponse::created($data);
    }
}
