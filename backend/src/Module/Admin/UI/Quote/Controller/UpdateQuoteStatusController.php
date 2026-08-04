<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Quote\DTO\QuoteStatusInput;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Service\QuoteCalculator;
use App\Module\Quote\Application\Service\QuoteStatusTranslator;
use App\Module\Quote\Application\Service\QuoteWorkflowService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
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

        $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
        $input = QuoteStatusInput::fromArray($payload);
        $this->validator->validate($input);
        $status = QuoteStatusTranslator::toCode($input->status);

        if (null !== $quote->getConvertedOrder() && Quote::STATUS_ACCEPTED !== $status) {
            return ApiResponse::error('Un devis converti doit rester accepté.', Response::HTTP_BAD_REQUEST);
        }

        $this->workflow->setStatus($quote, $status);

        return ApiResponse::success(QuoteFormatter::formatQuote($quote, $this->calculator), JsonResponse::HTTP_OK, 'Le statut du devis a bien été mis à jour.');
    }
}
