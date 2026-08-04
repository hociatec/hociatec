<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Service;

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

        return $this->directory.'/db-'.$date->format('Y-m-d_H-i-s').'.sql.gz.enc';
    }

    public function temporaryPathFor(\DateTimeImmutable $date): string
    {
        $this->ensureDirectory();

        return $this->directory.'/db-'.$date->format('Y-m-d_H-i-s').'.sql.gz.plain';
    }

    /** @return list<string> */
    public function legacyPaths(): array
    {
        $this->ensureDirectory();

        return array_values(array_filter(glob($this->directory.'/db-*.sql.gz') ?: [], 'is_file'));
    }

    /**
     * @return list<array{filename: string, sizeBytes: int, checksum: string, createdAt: string}>
     */
    public function list(): array
    {
        $this->ensureDirectory();
        $items = [];
        foreach (glob($this->directory.'/db-*.sql.gz.enc') ?: [] as $file) {
            if (is_file($file)) {
                $items[] = [
                    'filename' => basename($file),
                    'sizeBytes' => filesize($file) ?: 0,
                    'checksum' => hash_file('sha256', $file) ?: '',
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
        if (!is_dir($this->directory) && !mkdir($this->directory, 0750, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Impossible de créer le dossier de sauvegardes.');
        }
    }
}
