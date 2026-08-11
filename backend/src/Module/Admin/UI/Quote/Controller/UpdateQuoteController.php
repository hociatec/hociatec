<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Admin\Application\Quote\DTO\QuotePayloadInput;
use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Workflow\QuoteEmailService;
use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}', name: 'api_admin_quotes_update', methods: ['PUT', 'PATCH', 'POST'])]
#[IsGranted('ROLE_QUOTES_MANAGER')]
class UpdateQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quoteRepository,
        private readonly QuoteDomainService $quoteService,
        private readonly QuoteFormatter $formatter,
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

        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $input = QuotePayloadInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $quote = $this->quoteService->updateFromPayload($quote, QuotePayload::fromArray($input->toPayload()));
        } catch (\InvalidArgumentException|QuoteOperationException|\RuntimeException) {
            return ApiResponse::internalError();
        }

        $data = $this->formatter->formatQuote($quote);

        try {
            $data['emailNotificationSent'] = $this->quoteEmailService->sendCreatedIfNeeded($quote);
        } catch (\RuntimeException $exception) {
            $data['emailNotificationSent'] = false;
            $data['emailNotificationError'] = 'Notification email indisponible.';
        }

        return ApiResponse::success($data);
    }
}
