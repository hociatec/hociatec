<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Handler;

use App\Module\Admin\Application\Backup\State\BackupStateStore;

final readonly class RunDueBackupsHandler
{
    public function __construct(
        private BackupStateStore $states,
        private RunBackupHandler $runBackup,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function runDue(): ?array
    {
        $state = $this->states->read();
        $settings = $this->states->settings($state['settings'] ?? []);
        if (!$settings['enabled']) {
            return null;
        }
        $lastRunAt = $this->states->date($state['lastSuccessfulRunAt'] ?? null);
        if (null !== $lastRunAt && $lastRunAt->modify('+'.$settings['intervalHours'].' hours') > new \DateTimeImmutable()) {
            return null;
        }

        return $this->runBackup->run('scheduled');
    }
}
