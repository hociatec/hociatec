<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MaintenanceInput
{
    public function __construct(
        public bool $enabled,
        #[Assert\Length(max: 500)]
        public ?string $message,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_bool($payload['enabled'] ?? null) && $payload['enabled'],
            is_string($payload['message'] ?? null) ? trim($payload['message']) : null,
        );
    }
}
