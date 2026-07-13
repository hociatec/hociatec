<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes', name: 'api_admin_quotes_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListQuotesController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $search = $request->query->get('q');
        $status = $request->query->get('status');

        $quotes = $this->quoteRepository->findBySearch(
            $search !== null ? (string) $search : null,
            $status !== null ? (string) $status : null,
        );

        return ApiResponse::success([
            'items' => array_map(
                fn ($q) => QuoteFormatter::formatQuote($q, $this->calculator),
                $quotes
            ),
        ]);
    }
}

