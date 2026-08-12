<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Provider\OrderIssueInspector;
use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders', name: 'api_admin_orders_list', methods: ['GET'])]
#[IsGranted('ROLE_ORDERS_MANAGER')]
final class ListOrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly OrderEventRepositoryPort $events,
        private readonly OrderFormatter $orderFormatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $statusFilter = RequestQueryMapper::nullableString($request, 'status');
        $healthFilter = RequestQueryMapper::nullableString($request, 'health');
        $search = RequestQueryMapper::nullableString($request, 'q');
        $total = $this->orders->countForAdminList($statusFilter, $healthFilter, $search);
        $orders = $this->orders->findForAdminList($statusFilter, $healthFilter, $search, $pagination->perPage, $pagination->offset());
        $issueEventsByOrderId = $this->events->findIssueEventsGroupedByOrders($orders);

        $items = array_map(
            function (Order $order) use ($issueEventsByOrderId): array {
                $issueReasons = OrderIssueInspector::getOperationalIssues(
                    $order,
                    $issueEventsByOrderId[$order->getId() ?? 0] ?? [],
                );

                return $this->orderFormatter->formatOrder($order, [], [
                    'hasIssues' => [] !== $issueReasons,
                    'issueReasons' => $issueReasons,
                ]);
            },
            $orders,
        );

        return ApiResponse::paginated($items, $pagination->metadata($total));
    }
}
