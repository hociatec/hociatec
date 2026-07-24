<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Admin\Operations\Service\BulkOrderStatusService;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/orders/bulk-status', name: 'api_admin_operations_orders_bulk_status', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class BulkOrderStatusController
{
    public function __construct(private BulkOrderStatusService $bulkStatus)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $ids = array_values(array_filter(array_map('intval', (array) ($payload['orderIds'] ?? []))));
            $updated = $this->bulkStatus->update($ids, (string) ($payload['status'] ?? ''));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['updated' => $updated]);
    }
}
