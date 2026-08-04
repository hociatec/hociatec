<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins', name: 'api_admin_trade_ins_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListTradeInsController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepository $requests)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $statusValue = $request->query->get('status');
        $status = is_string($statusValue) ? TradeInStatus::tryFrom($statusValue) : null;

        return ApiResponse::successItem('items', array_map(static fn ($item) => TradeInFormatter::format($item), $this->requests->findForAdmin(is_string($request->query->get('q')) ? $request->query->get('q') : null, $status)));
    }
}
