<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Operations\Service\CustomerTimelineProvider;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/customers/{id}/timeline', name: 'api_admin_operations_customer_timeline', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class CustomerTimelineController
{
    public function __construct(private CustomerTimelineProvider $timeline)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        try {
            return ApiResponse::success(['items' => $this->timeline->provide($id)]);
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }
}
