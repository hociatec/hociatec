<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins', name: 'api_admin_trade_ins_list', methods: ['GET'])]
#[IsGranted('ROLE_TRADE_INS_MANAGER')]
final class ListTradeInsController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepositoryPort $requests)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $statusValue = $request->query->get('status');
        $status = is_string($statusValue) ? TradeInStatus::tryFrom($statusValue) : null;
        $search = is_string($request->query->get('q')) ? $request->query->get('q') : null;
        $items = $this->requests->findForAdmin($search, $status, $pagination->perPage, $pagination->offset());
        $total = $this->requests->countForAdmin($search, $status);

        return ApiResponse::paginated(array_map(static fn ($item) => TradeInFormatter::format($item), $items), $pagination->metadata($total));
    }
}
