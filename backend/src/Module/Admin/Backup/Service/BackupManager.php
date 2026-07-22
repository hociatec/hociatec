<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Service;

final class BackupManager
{
    private const DEFAULT_INTERVAL_HOURS = 24;
    private const DEFAULT_RETENTION_COUNT = 7;
    private const MAX_HISTORY = 80;

    private string $backupDir;
    private string $stateFile;
    private string $lockFile;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $databaseUrl,
        private readonly MaintenanceModeService $maintenanceModeService,
    ) {
        $this->backupDir = $this->projectDir . '/var/backups';
        $this->stateFile = $this->backupDir . '/backup-state.json';
        $this->lockFile = $this->backupDir . '/backup.lock';
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $state = $this->readState();
        $settings = $this->normalizeSettings($state['settings'] ?? []);

        return [
            'settings' => $this->settingsForOutput($settings, $state),
            'backups' => $this->listBackups(),
            'history' => array_slice($this->normalizeHistory($state['history'] ?? []), 0, self::MAX_HISTORY),
            'maintenance' => $this->maintenanceModeService->getStatus(),
            'tools' => [
                'mysqldumpAvailable' => $this->isExecutableAvailable('mysqldump'),
                'gzipAvailable' => extension_loaded('zlib'),
            ],
            'scheduler' => [
                'command' => 'cd ' . $this->projectDir . ' && APP_ENV=prod APP_DEBUG=0 php bin/console app:backups:run-due',
                'cronExample' => '*/15 * * * * cd ' . $this->projectDir . ' && APP_ENV=prod APP_DEBUG=0 php bin/console app:backups:run-due >> var/log/backup-cron.log 2>&1',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateSettings(array $payload): array
    {
        $state = $this->readState();
        $current = $this->normalizeSettings($state['settings'] ?? []);

        $settings = [
            'enabled' => array_key_exists('enabled', $payload) ? (bool) $payload['enabled'] : $current['enabled'],
            'intervalHours' => $this->intInRange($payload['intervalHours'] ?? $current['intervalHours'], 1, 720, 'intervalHours'),
            'retentionCount' => $this->intInRange($payload['retentionCount'] ?? $current['retentionCount'], 1, 90, 'retentionCount'),
        ];

        $state['settings'] = $settings;
        $this->writeState($state);
        $this->applyRetention($settings['retentionCount']);

        return $this->getStatus();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function runDue(): ?array
    {
        $state = $this->readState();
        $settings = $this->normalizeSettings($state['settings'] ?? []);
        if (!$settings['enabled']) {
            return null;
        }

        $lastRunAt = $this->parseDate($state['lastSuccessfulRunAt'] ?? null);
        if ($lastRunAt !== null) {
            $nextRunAt = $lastRunAt->modify('+' . $settings['intervalHours'] . ' hours');
            if ($nextRunAt > new \DateTimeImmutable()) {
                return null;
            }
        }

        return $this->runBackup('scheduled');
    }

    /**
     * @return array<string, mixed>
     */
    public function runBackup(string $trigger = 'manual'): array
    {
        $this->ensureBackupDir();

        $lock = fopen($this->lockFile, 'c');
        if ($lock === false) {
            throw new \RuntimeException('Impossible de créer le verrou de sauvegarde.');
        }

        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new \RuntimeException('Une sauvegarde est déjà en cours.');
        }

        try {
            $startedAt = new \DateTimeImmutable();
            $filename = 'db-' . $startedAt->format('Y-m-d_H-i-s') . '.sql.gz';
            $path = $this->backupDir . '/' . $filename;
            $run = [
                'id' => $startedAt->format('YmdHis'),
                'status' => 'running',
                'trigger' => $trigger,
                'filename' => $filename,
                'startedAt' => $startedAt->format(\DateTimeInterface::ATOM),
                'finishedAt' => null,
                'sizeBytes' => null,
                'message' => 'Sauvegarde en cours.',
            ];

            $state = $this->recordRun($run);

            try {
                $this->dumpDatabase($path);
                $finishedAt = new \DateTimeImmutable();
                $run['status'] = 'success';
                $run['finishedAt'] = $finishedAt->format(\DateTimeInterface::ATOM);
                $run['sizeBytes'] = is_file($path) ? filesize($path) : 0;
                $run['message'] = 'Sauvegarde terminée.';

                $state['lastSuccessfulRunAt'] = $run['finishedAt'];
                $state = $this->recordRun($run, $state);
                $settings = $this->normalizeSettings($state['settings'] ?? []);
                $this->applyRetention($settings['retentionCount']);

                return $this->getStatus();
            } catch (\Throwable $e) {
                if (is_file($path)) {
                    unlink($path);
                }

                $run['status'] = 'failed';
                $run['finishedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
                $run['message'] = $e->getMessage();
                $this->recordRun($run, $state);

                throw $e;
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function recordRun(array $run, ?array $state = null): array
    {
        $state ??= $this->readState();
        $history = $this->normalizeHistory($state['history'] ?? []);
        $history = array_values(array_filter(
            $history,
            static fn (array $item): bool => ($item['id'] ?? null) !== ($run['id'] ?? null)
        ));
        array_unshift($history, $run);
        $state['history'] = array_slice($history, 0, self::MAX_HISTORY);
        $this->writeState($state);

        return $state;
    }

    private function dumpDatabase(string $targetPath): void
    {
        $database = $this->parseDatabaseUrl();
        if (!$this->isExecutableAvailable('mysqldump')) {
            throw new \RuntimeException('mysqldump est introuvable sur le serveur.');
        }

        $command = [
            'mysqldump',
            '-u' . $database['user'],
            '-h' . $database['host'],
            '--single-transaction',
            '--routines',
            '--triggers',
            $database['name'],
        ];

        if ($database['port'] !== null) {
            array_splice($command, 4, 0, ['-P' . $database['port']]);
        }

        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $this->projectDir, [
            'MYSQL_PWD' => $database['password'],
            'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ]);

        if (!is_resource($process)) {
            throw new \RuntimeException('Impossible de lancer mysqldump.');
        }

        $gz = gzopen($targetPath, 'wb9');
        if ($gz === false) {
            proc_terminate($process);
            throw new \RuntimeException('Impossible de créer le fichier de sauvegarde.');
        }

        while (!feof($pipes[1])) {
            $chunk = fread($pipes[1], 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            gzwrite($gz, $chunk);
        }

        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);
        gzclose($gz);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            throw new \RuntimeException(trim($stderr) !== '' ? trim($stderr) : 'mysqldump a échoué.');
        }
    }

    /**
     * @return array{user: string, password: string, host: string, port: int|null, name: string}
     */
    private function parseDatabaseUrl(): array
    {
        $parts = parse_url($this->databaseUrl);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'mysql') {
            throw new \RuntimeException('DATABASE_URL doit utiliser le protocole mysql.');
        }

        $name = isset($parts['path']) ? ltrim((string) $parts['path'], '/') : '';
        if ($name === '' || !isset($parts['user'], $parts['host'])) {
            throw new \RuntimeException('DATABASE_URL est incomplet.');
        }

        return [
            'user' => rawurldecode((string) $parts['user']),
            'password' => rawurldecode((string) ($parts['pass'] ?? '')),
            'host' => (string) $parts['host'],
            'port' => isset($parts['port']) ? (int) $parts['port'] : null,
            'name' => rawurldecode($name),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listBackups(): array
    {
        $this->ensureBackupDir();
        $files = glob($this->backupDir . '/db-*.sql.gz') ?: [];
        $items = [];

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $items[] = [
                'filename' => basename($file),
                'sizeBytes' => filesize($file) ?: 0,
                'createdAt' => (new \DateTimeImmutable('@' . (filemtime($file) ?: time())))->format(\DateTimeInterface::ATOM),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $b['createdAt'], (string) $a['createdAt']));

        return $items;
    }

    private function applyRetention(int $retentionCount): void
    {
        $backups = $this->listBackups();
        foreach (array_slice($backups, $retentionCount) as $backup) {
            $filename = basename((string) $backup['filename']);
            $path = $this->backupDir . '/' . $filename;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readState(): array
    {
        $this->ensureBackupDir();
        if (!is_file($this->stateFile)) {
            return ['settings' => $this->normalizeSettings([]), 'history' => []];
        }

        $content = file_get_contents($this->stateFile);
        $data = $content !== false ? json_decode($content, true) : null;

        return is_array($data) ? $data : ['settings' => $this->normalizeSettings([]), 'history' => []];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeState(array $state): void
    {
        $this->ensureBackupDir();
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($this->stateFile, $json . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('Impossible de sauvegarder la configuration des sauvegardes.');
        }
    }

    /**
     * @param mixed $settings
     * @return array{enabled: bool, intervalHours: int, retentionCount: int}
     */
    private function normalizeSettings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];

        return [
            'enabled' => (bool) ($settings['enabled'] ?? false),
            'intervalHours' => $this->intInRange($settings['intervalHours'] ?? self::DEFAULT_INTERVAL_HOURS, 1, 720, 'intervalHours'),
            'retentionCount' => $this->intInRange($settings['retentionCount'] ?? self::DEFAULT_RETENTION_COUNT, 1, 90, 'retentionCount'),
        ];
    }

    /**
     * @param array{enabled: bool, intervalHours: int, retentionCount: int} $settings
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function settingsForOutput(array $settings, array $state): array
    {
        $lastRunAt = is_string($state['lastSuccessfulRunAt'] ?? null) ? $state['lastSuccessfulRunAt'] : null;
        $lastDate = $this->parseDate($lastRunAt);
        $nextRunAt = null;
        if ($settings['enabled']) {
            $nextRunAt = $lastDate === null
                ? (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                : $lastDate->modify('+' . $settings['intervalHours'] . ' hours')->format(\DateTimeInterface::ATOM);
        }

        return $settings + [
            'lastSuccessfulRunAt' => $lastRunAt,
            'nextRunAt' => $nextRunAt,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeHistory(mixed $history): array
    {
        if (!is_array($history)) {
            return [];
        }

        return array_values(array_filter($history, static fn (mixed $item): bool => is_array($item)));
    }

    private function intInRange(mixed $value, int $min, int $max, string $field): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            throw new \InvalidArgumentException(sprintf('%s doit être compris entre %d et %d.', $field, $min, $max));
        }

        return $value;
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function ensureBackupDir(): void
    {
        if (!is_dir($this->backupDir) && !mkdir($this->backupDir, 0775, true) && !is_dir($this->backupDir)) {
            throw new \RuntimeException('Impossible de créer le dossier de sauvegardes.');
        }
    }

    private function isExecutableAvailable(string $name): bool
    {
        $result = trim((string) shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null'));

        return $result !== '';
    }
}
