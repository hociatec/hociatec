<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/{id}', name: 'api_admin_quotes_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteDomainService $quoteService,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $quote = $this->quoteRepository->find($id);
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->quoteService->delete($quote);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'Le devis a bien été supprimé.');
    }
}
