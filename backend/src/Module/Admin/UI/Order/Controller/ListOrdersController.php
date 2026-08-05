<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Provider\OrderIssueInspector;
use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
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
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 25, 100);
        $statusFilter = RequestQueryMapper::nullableString($request, 'status');
        $healthFilter = RequestQueryMapper::nullableString($request, 'health');
        $total = $this->orders->countForAdminList($statusFilter, $healthFilter);
        $orders = $this->orders->findForAdminList($statusFilter, $healthFilter, $pagination->perPage, $pagination->offset());
        $issueEventsByOrderId = $this->events->findIssueEventsGroupedByOrders($orders);

        $items = array_map(
            static function (Order $order) use ($issueEventsByOrderId): array {
                $issueReasons = OrderIssueInspector::getOperationalIssues(
                    $order,
                    $issueEventsByOrderId[$order->getId() ?? 0] ?? [],
                );

                return OrderFormatter::formatOrder($order, [], [
                    'hasIssues' => [] !== $issueReasons,
                    'issueReasons' => $issueReasons,
                ]);
            },
            $orders,
        );

        return ApiResponse::paginated($items, $pagination->metadata($total));
    }
}
