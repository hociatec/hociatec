<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Backup\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\Backup\Service\MaintenanceModeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SystemStatusController
{
    public function __construct(private readonly MaintenanceModeService $maintenanceModeService)
    {
    }

    #[Route('/api/public/system/status', name: 'api_public_system_status', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'maintenance' => $this->maintenanceModeService->getStatus(),
        ]);
    }
}
