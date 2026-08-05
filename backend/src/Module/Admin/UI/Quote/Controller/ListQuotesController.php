<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Mapper\QuoteStatusTranslator;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes', name: 'api_admin_quotes_list', methods: ['GET'])]
#[IsGranted('ROLE_QUOTES_MANAGER')]
class ListQuotesController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepositoryPort $quoteRepository,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $search = $request->query->get('q');
        $status = $request->query->get('status');
        $searchFilter = null !== $search ? (string) $search : null;
        $statusFilter = null !== $status ? QuoteStatusTranslator::toCode((string) $status) : null;

        $quotes = $this->quoteRepository->findBySearch(
            $searchFilter,
            $statusFilter,
            $pagination->perPage,
            $pagination->offset(),
        );
        $total = $this->quoteRepository->countBySearch($searchFilter, $statusFilter);

        return ApiResponse::paginated(
            array_map(
                fn ($q) => QuoteFormatter::formatQuote($q, $this->calculator),
                $quotes
            ),
            $pagination->metadata($total),
        );
    }
}
