<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Service;

final class MaintenanceModeService
{
    private string $file;

    public function __construct(string $projectDir)
    {
        $this->file = $projectDir . '/var/maintenance.json';
    }

    /**
     * @return array{enabled: bool, message: string, updatedAt: string|null}
     */
    public function getStatus(): array
    {
        $data = $this->read();

        return [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'message' => $this->normalizeMessage($data['message'] ?? null),
            'updatedAt' => is_string($data['updatedAt'] ?? null) ? $data['updatedAt'] : null,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->getStatus()['enabled'];
    }

    public function set(bool $enabled, ?string $message = null): array
    {
        $status = [
            'enabled' => $enabled,
            'message' => $this->normalizeMessage($message),
            'updatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $this->write($status);

        return $status;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $content = file_get_contents($this->file);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function write(array $data): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Impossible de préparer le dossier de maintenance.');
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($this->file, $json . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('Impossible de sauvegarder le mode maintenance.');
        }
    }

    private function normalizeMessage(mixed $message): string
    {
        $value = is_string($message) ? trim($message) : '';

        return $value !== '' ? $value : 'Le site est temporairement en maintenance. Merci de revenir dans quelques minutes.';
    }
}
