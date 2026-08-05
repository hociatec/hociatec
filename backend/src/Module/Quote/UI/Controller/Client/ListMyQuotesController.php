<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\Client;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me', name: 'api_quotes_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyQuotesController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quotes,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();
        $quotes = $this->quotes->findByCustomerEmail($user->getEmail(), $pagination->perPage, $pagination->offset());
        $items = array_map(
            fn ($q) => QuoteFormatter::formatQuote($q, $this->calculator),
            $quotes,
        );

        return ApiResponse::paginated($items, $pagination->metadata($this->quotes->countByCustomerEmail($user->getEmail())));
    }
}
