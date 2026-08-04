<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Service;

final readonly class RunBackupHandler
{
    public function __construct(
        private BackupStateStore $states,
        private BackupFileStorage $files,
        private BackupEncryptionService $encryption,
        private DatabaseBackupDumper $database,
        private BackupStatusProvider $statusProvider,
    ) {
    }

    /** @return array<string, mixed> */
    public function run(string $trigger = 'manual'): array
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
        $temporaryPath = $this->files->temporaryPathFor($startedAt);
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
            $this->database->dump($temporaryPath);
            $this->encryption->encryptFile($temporaryPath, $path);
            $this->files->delete($temporaryPath);
            $run['status'] = 'success';
            $run['finishedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
            $run['sizeBytes'] = is_file($path) ? filesize($path) : 0;
            $run['message'] = 'Sauvegarde terminée.';
            $state['lastSuccessfulRunAt'] = $run['finishedAt'];
            $state = $this->states->recordRun($run, $state);
            $this->files->applyRetention($this->states->settings($state['settings'] ?? [])['retentionCount']);

            return $this->statusProvider->status();
        } catch (\RuntimeException $exception) {
            $this->files->delete($path);
            $this->files->delete($temporaryPath);
            $run['status'] = 'failed';
            $run['finishedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
            $run['message'] = $exception->getMessage();
            $this->states->recordRun($run, $state);
            throw $exception;
        }
    }
}
