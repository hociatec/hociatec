<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Service;

final readonly class UpdateBackupSettingsHandler
{
    public function __construct(
        private BackupStateStore $states,
        private BackupFileStorage $files,
        private BackupStatusProvider $statusProvider,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(array $payload): array
    {
        $state = $this->states->read();
        $settings = $this->states->mergeSettings($this->states->settings($state['settings'] ?? []), $payload);
        $state['settings'] = $settings;
        $this->states->write($state);
        $this->files->applyRetention($settings['retentionCount']);

        return $this->statusProvider->status();
    }
}
