<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Service;

final readonly class BackupStateStore
{
    private const MAX_HISTORY = 80;
    private string $stateFile;

    public function __construct(string $projectDir)
    {
        $this->stateFile = $projectDir.'/var/backups/backup-state.json';
    }

    /** @return array<string, mixed> */
    public function read(): array
    {
        $this->ensureDirectory();
        if (!is_file($this->stateFile)) {
            return ['settings' => $this->settings([]), 'history' => []];
        }
        $content = file_get_contents($this->stateFile);
        $data = false !== $content ? json_decode($content, true) : null;

        return is_array($data) ? $data : ['settings' => $this->settings([]), 'history' => []];
    }

    /** @param array<string, mixed> $state */
    public function write(array $state): void
    {
        $this->ensureDirectory();
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false === $json || false === file_put_contents($this->stateFile, $json."\n", LOCK_EX)) {
            throw new \RuntimeException('Impossible de sauvegarder la configuration des sauvegardes.');
        }
    }

    /** @return array{enabled: bool, intervalHours: int, retentionCount: int} */
    public function settings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];

        return [
            'enabled' => (bool) ($settings['enabled'] ?? false),
            'intervalHours' => $this->integer($settings['intervalHours'] ?? 24, 1, 720, 'intervalHours'),
            'retentionCount' => $this->integer($settings['retentionCount'] ?? 7, 1, 90, 'retentionCount'),
        ];
    }

    /**
     * @param array{enabled: bool, intervalHours: int, retentionCount: int} $current
     * @param array<string, mixed>                                          $payload
     *
     * @return array{enabled: bool, intervalHours: int, retentionCount: int}
     */
    public function mergeSettings(array $current, array $payload): array
    {
        return [
            'enabled' => array_key_exists('enabled', $payload) ? (bool) $payload['enabled'] : $current['enabled'],
            'intervalHours' => $this->integer($payload['intervalHours'] ?? $current['intervalHours'], 1, 720, 'intervalHours'),
            'retentionCount' => $this->integer($payload['retentionCount'] ?? $current['retentionCount'], 1, 90, 'retentionCount'),
        ];
    }

    /**
     * @param array{enabled: bool, intervalHours: int, retentionCount: int} $settings
     * @param array<string, mixed>                                          $state
     *
     * @return array{enabled: bool, intervalHours: int, retentionCount: int, lastSuccessfulRunAt: string|null, nextRunAt: string|null}
     */
    public function outputSettings(array $settings, array $state): array
    {
        $lastRunAt = is_string($state['lastSuccessfulRunAt'] ?? null) ? $state['lastSuccessfulRunAt'] : null;
        $lastDate = $this->date($lastRunAt);
        $nextRunAt = null;
        if ($settings['enabled']) {
            $nextRunAt = null === $lastDate
                ? (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                : $lastDate->modify('+'.$settings['intervalHours'].' hours')->format(\DateTimeInterface::ATOM);
        }

        return $settings + ['lastSuccessfulRunAt' => $lastRunAt, 'nextRunAt' => $nextRunAt];
    }

    /** @return list<array<string, mixed>> */
    public function history(mixed $history): array
    {
        return is_array($history)
            ? array_values(array_filter($history, static fn (mixed $item): bool => is_array($item)))
            : [];
    }

    /**
     * @param array<string, mixed>      $run
     * @param array<string, mixed>|null $state
     *
     * @return array<string, mixed>
     */
    public function recordRun(array $run, ?array $state = null): array
    {
        $state ??= $this->read();
        $history = array_values(array_filter(
            $this->history($state['history'] ?? []),
            static fn (array $item): bool => ($item['id'] ?? null) !== ($run['id'] ?? null),
        ));
        array_unshift($history, $run);
        $state['history'] = array_slice($history, 0, self::MAX_HISTORY);
        $this->write($state);

        return $state;
    }

    public function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function integer(mixed $value, int $min, int $max, string $field): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if (false === $value || $value < $min || $value > $max) {
            throw new \InvalidArgumentException(sprintf('%s doit être compris entre %d et %d.', $field, $min, $max));
        }

        return $value;
    }

    private function ensureDirectory(): void
    {
        $directory = dirname($this->stateFile);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de créer le dossier de sauvegardes.');
        }
    }
}
