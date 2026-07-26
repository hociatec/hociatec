<?php

declare(strict_types=1);

namespace App\Module\Admin\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BackupSettingsInput
{
    public function __construct(public ?bool $enabled, #[Assert\Range(min: 1, max: 720)] public ?int $intervalHours, #[Assert\Range(min: 1, max: 90)] public ?int $retentionCount, public bool $maintenanceEnabled = false, #[Assert\Length(max: 500)] public ?string $message = null)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_bool($p['enabled'] ?? null) ? $p['enabled'] : null, is_numeric($p['intervalHours'] ?? null) ? (int) $p['intervalHours'] : null, is_numeric($p['retentionCount'] ?? null) ? (int) $p['retentionCount'] : null, is_bool($p['enabled'] ?? null) ? $p['enabled'] : false, is_string($p['message'] ?? null) ? trim($p['message']) : null);
    }

    /** @return array<string,mixed> */
    public function settings(): array
    {
        return array_filter(['enabled' => $this->enabled, 'intervalHours' => $this->intervalHours, 'retentionCount' => $this->retentionCount], static fn (mixed $v): bool => null !== $v);
    }
}
