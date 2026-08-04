<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Operations\DTO\BulkOrderStatusInput;
use App\Module\Admin\Application\Operations\Service\BulkOrderStatusService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/orders/bulk-status', name: 'api_admin_operations_orders_bulk_status', methods: ['POST'])]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class BulkOrderStatusController
{
    public function __construct(
        private BulkOrderStatusService $bulkStatus,
        private DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Infrastructure\Http\JsonRequestInput::payload($request);
            $input = BulkOrderStatusInput::fromArray($payload);
            $this->validator->validate($input);
            $updated = $this->bulkStatus->update($input->orderIds, $input->status);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::successItem('updated', $updated);
    }
}
