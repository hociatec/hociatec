<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Service;

final readonly class BackupManager
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
    public function getStatus(): array
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

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function updateSettings(array $payload): array
    {
        $state = $this->states->read();
        $settings = $this->states->mergeSettings($this->states->settings($state['settings'] ?? []), $payload);
        $state['settings'] = $settings;
        $this->states->write($state);
        $this->files->applyRetention($settings['retentionCount']);

        return $this->getStatus();
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

        return $this->runBackup('scheduled');
    }

    /** @return array<string, mixed> */
    public function runBackup(string $trigger = 'manual'): array
    {
        $lock = fopen($this->files->lockFile(), 'c');
        if (false === $lock) {
            throw new \RuntimeException('Impossible de créer le verrou de sauvegarde.');
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new \RuntimeException('Une sauvegarde est déjà en cours.');
        }
        try {
            return $this->execute($trigger);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return array<string, mixed> */
    private function execute(string $trigger): array
    {
        $startedAt = new \DateTimeImmutable();
        $path = $this->files->pathFor($startedAt);
        $run = [
            'id' => $startedAt->format('YmdHis'),
            'status' => 'running',
            'trigger' => $trigger,
            'filename' => basename($path),
            'startedAt' => $startedAt->format(\DateTimeInterface::ATOM),
            'finishedAt' => null,
            'sizeBytes' => null,
            'message' => 'Sauvegarde en cours.',
        ];
        $state = $this->states->recordRun($run);
        try {
            $this->database->dump($path);
            $run['status'] = 'success';
            $run['finishedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
            $run['sizeBytes'] = is_file($path) ? filesize($path) : 0;
            $run['message'] = 'Sauvegarde terminée.';
            $state['lastSuccessfulRunAt'] = $run['finishedAt'];
            $state = $this->states->recordRun($run, $state);
            $this->files->applyRetention($this->states->settings($state['settings'] ?? [])['retentionCount']);

            return $this->getStatus();
        } catch (\Throwable $exception) {
            $this->files->delete($path);
            $run['status'] = 'failed';
            $run['finishedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
            $run['message'] = $exception->getMessage();
            $this->states->recordRun($run, $state);
            throw $exception;
        }
    }
}
