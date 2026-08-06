<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Mapper\QuoteStatusTranslator;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
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
        private readonly QuoteFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $searchFilter = RequestQueryMapper::nullableString($request, 'q');
        $status = RequestQueryMapper::nullableString($request, 'status');
        $statusFilter = null !== $status ? QuoteStatusTranslator::toCode($status) : null;
        $from = RequestQueryMapper::dateTime($request, 'from');
        $to = RequestQueryMapper::dateTime($request, 'to');

        $quotes = $this->quoteRepository->findBySearch(
            $searchFilter,
            $statusFilter,
            $pagination->perPage,
            $pagination->offset(),
            $from,
            $to,
        );
        $total = $this->quoteRepository->countBySearch($searchFilter, $statusFilter, $from, $to);

        return ApiResponse::paginated(
            array_map(
                fn ($q) => $this->formatter->formatQuote($q),
                $quotes
            ),
            $pagination->metadata($total),
        );
    }
}
