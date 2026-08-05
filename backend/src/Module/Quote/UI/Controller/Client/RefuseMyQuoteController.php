<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\Client;

use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Security\QuoteAccessPolicy;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me/{id}/refuse', name: 'api_quotes_me_refuse', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class RefuseMyQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quotes,
        private readonly QuoteFormatter $formatter,
        private readonly QuoteWorkflowService $workflow,
        private readonly QuoteAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $quote = $this->quotes->find($id);

        if (null === $quote || !$this->accessPolicy->canView($user, $quote)) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (null !== $quote->getConvertedOrder()) {
            return ApiResponse::error('Ce devis est déjà converti en commande.', Response::HTTP_CONFLICT);
        }

        if (!in_array($quote->getStatus(), [Quote::STATUS_SENT, Quote::STATUS_REFUSED], true)) {
            return ApiResponse::error('Ce devis ne peut pas être refusé.', Response::HTTP_BAD_REQUEST);
        }

        $this->workflow->setStatus($quote, Quote::STATUS_REFUSED);

        return ApiResponse::success($this->formatter->formatQuote($quote), JsonResponse::HTTP_OK, 'Le devis a bien été refusé.');
    }
}
