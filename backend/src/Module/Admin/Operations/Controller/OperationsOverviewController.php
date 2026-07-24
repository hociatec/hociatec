<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Admin\Operations\Service\OperationsOverviewProvider;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/overview', name: 'api_admin_operations_overview', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class OperationsOverviewController
{
    public function __construct(private OperationsOverviewProvider $overview)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success($this->overview->provide());
    }
}
