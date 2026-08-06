<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Provider;

use App\Module\Admin\Application\Backup\Port\DatabaseBackupDumper;
use App\Module\Admin\Application\Backup\State\BackupStateStore;
use App\Module\Admin\Application\Backup\Storage\BackupFileStorage;
use App\Module\Admin\Application\Backup\Workflow\MaintenanceModeService;

final readonly class BackupStatusProvider
{
    public function __construct(
        private string $projectDir,
        private MaintenanceModeService $maintenance,
        private BackupStateStore $states,
        private BackupFileStorage $files,
        private DatabaseBackupDumper $database,
    ) {
    }

    /** @return array<string, mixed> */
    public function status(): array
    {
        $state = $this->states->read();
        $settings = $this->states->settings($state['settings'] ?? []);

        return [
            'settings' => $this->states->outputSettings($settings, $state),
            'backups' => $this->files->list(),
            'history' => array_slice($this->states->history($state['history'] ?? []), 0, 80),
            'maintenance' => $this->maintenance->getStatus(),
            'tools' => ['mysqldumpAvailable' => $this->database->isAvailable(), 'gzipAvailable' => extension_loaded('zlib')],
            'scheduler' => [
                'command' => 'cd '.$this->projectDir.' && APP_ENV=prod APP_DEBUG=0 php bin/console app:backups:run-due',
                'cronExample' => '*/15 * * * * cd '.$this->projectDir.' && APP_ENV=prod APP_DEBUG=0 php bin/console app:backups:run-due >> var/log/backup-cron.log 2>&1',
            ],
        ];
    }
}
