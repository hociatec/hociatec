<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Controller;

use App\Module\Admin\Backup\Service\BackupManager;
use App\Module\Admin\Backup\Service\MaintenanceModeService;
use App\Module\Admin\DTO\BackupSettingsInput;
use App\Module\Admin\DTO\MaintenanceInput;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Validation\DtoValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/backups')]
#[IsGranted('ROLE_BACKUP_MANAGER')]
final class AdminBackupController
{
    public function __construct(
        private readonly BackupManager $backupManager,
        private readonly MaintenanceModeService $maintenanceModeService,
        private readonly DtoValidator $validator,
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
            $payload = JsonPayload::decode($request);
            $input = BackupSettingsInput::fromArray($payload);
            $this->validator->validate($input);

            return ApiResponse::success($this->backupManager->updateSettings($input->settings()), Response::HTTP_OK, 'La configuration des sauvegardes a bien été enregistrée.');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return ApiResponse::internalError();
        }
    }

    #[Route('/run', name: 'api_admin_backups_run', methods: ['POST'])]
    public function run(): JsonResponse
    {
        try {
            return ApiResponse::success($this->backupManager->runBackup('manual'), Response::HTTP_OK, 'La sauvegarde a bien été exécutée.');
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return ApiResponse::internalError();
        }
    }

    #[Route('/maintenance', name: 'api_admin_backups_maintenance', methods: ['POST'])]
    public function maintenance(Request $request): JsonResponse
    {
        try {
            $payload = JsonPayload::decode($request);
            $input = MaintenanceInput::fromArray($payload);
            $this->validator->validate($input);

            return ApiResponse::success([
                'maintenance' => $this->maintenanceModeService->set($input->enabled, $input->message),
            ], Response::HTTP_OK, $input->enabled ? 'Le mode maintenance a été activé.' : 'Le mode maintenance a été désactivé.');
        } catch (\Exception $e) {
            return ApiResponse::internalError();
        }
    }
}
