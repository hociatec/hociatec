<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Quote\DTO\QuotePayloadInput;
use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Service\QuoteCalculator;
use App\Module\Quote\Application\Service\QuoteEmailService;
use App\Module\Quote\Application\Service\QuoteService as QuoteDomainService;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
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

        $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
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
