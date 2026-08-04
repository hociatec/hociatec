<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Admin\Quote\DTO\QuotePayloadInput;
use App\Module\Quote\DTO\QuotePayload;
use App\Module\Quote\Exception\QuoteOperationException;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteEmailService;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteService as QuoteDomainService;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}', name: 'api_admin_quotes_update', methods: ['PUT', 'PATCH', 'POST'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteDomainService $quoteService,
        private readonly QuoteCalculator $calculator,
        private readonly QuoteEmailService $quoteEmailService,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $input = QuotePayloadInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $quote = $this->quoteService->updateFromPayload($quote, QuotePayload::fromArray($input->toPayload()));
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

        return ApiResponse::success($data);
    }
}
