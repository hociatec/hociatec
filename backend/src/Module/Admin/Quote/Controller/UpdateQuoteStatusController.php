<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Admin\Quote\DTO\QuoteStatusInput;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Quote\Service\QuoteStatusTranslator;
use App\Module\Quote\Service\QuoteWorkflowService;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
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
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $quote = $this->quotes->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $input = QuoteStatusInput::fromArray($payload);
        $this->validator->validate($input);
        $status = QuoteStatusTranslator::toCode($input->status);

        if (null !== $quote->getConvertedOrder() && Quote::STATUS_ACCEPTED !== $status) {
            return ApiResponse::error('Un devis converti doit rester accepté.', Response::HTTP_BAD_REQUEST);
        }

        $this->workflow->setStatus($quote, $status);

        return ApiResponse::success(QuoteFormatter::formatQuote($quote, $this->calculator));
    }
}
