<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Controller;

use App\Module\Admin\Backup\Service\BackupManager;
use App\Module\Admin\Backup\Service\MaintenanceModeService;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/backups')]
#[IsGranted('ROLE_ADMIN')]
final class AdminBackupController
{
    public function __construct(
        private readonly BackupManager $backupManager,
        private readonly MaintenanceModeService $maintenanceModeService,
    ) {
    }

    #[Route('', name: 'api_admin_backups_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return ApiResponse::success($this->backupManager->getStatus());
    }

    #[Route('/settings', name: 'api_admin_backups_settings', methods: ['PATCH'])]
    public function settings(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();

            return ApiResponse::success($this->backupManager->updateSettings($payload));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/run', name: 'api_admin_backups_run', methods: ['POST'])]
    public function run(): JsonResponse
    {
        try {
            return ApiResponse::success($this->backupManager->runBackup('manual'));
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_CONFLICT);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/maintenance', name: 'api_admin_backups_maintenance', methods: ['POST'])]
    public function maintenance(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $enabled = (bool) ($payload['enabled'] ?? false);
            $message = is_string($payload['message'] ?? null) ? $payload['message'] : null;

            return ApiResponse::success([
                'maintenance' => $this->maintenanceModeService->set($enabled, $message),
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
