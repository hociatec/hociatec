<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Service;

final readonly class BackupFileStorage
{
    private string $directory;

    public function __construct(string $projectDir)
    {
        $this->directory = $projectDir.'/var/backups';
    }

    public function lockFile(): string
    {
        $this->ensureDirectory();

        return $this->directory.'/backup.lock';
    }

    public function pathFor(\DateTimeImmutable $date): string
    {
        $this->ensureDirectory();

        return $this->directory.'/db-'.$date->format('Y-m-d_H-i-s').'.sql.gz';
    }

    /**
     * @return list<array{filename: string, sizeBytes: int, createdAt: string}>
     */
    public function list(): array
    {
        $this->ensureDirectory();
        $items = [];
        foreach (glob($this->directory.'/db-*.sql.gz') ?: [] as $file) {
            if (is_file($file)) {
                $items[] = [
                    'filename' => basename($file),
                    'sizeBytes' => filesize($file) ?: 0,
                    'createdAt' => (new \DateTimeImmutable('@'.(filemtime($file) ?: time())))->format(\DateTimeInterface::ATOM),
                ];
            }
        }
        usort($items, static fn (array $a, array $b): int => strcmp((string) $b['createdAt'], (string) $a['createdAt']));

        return $items;
    }

    public function applyRetention(int $retentionCount): void
    {
        foreach (array_slice($this->list(), $retentionCount) as $backup) {
            $this->delete($this->directory.'/'.basename((string) $backup['filename']));
        }
    }

    public function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Impossible de créer le dossier de sauvegardes.');
        }
    }
}
