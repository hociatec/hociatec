<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\Client;

use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Domain\Security\QuoteAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me/{id}', name: 'api_quotes_me_show', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class GetMyQuoteController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quotes,
        private readonly QuoteFormatter $formatter,
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

        return ApiResponse::success($this->formatter->formatQuote($quote));
    }
}
