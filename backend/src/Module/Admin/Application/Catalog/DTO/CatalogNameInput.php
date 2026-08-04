<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CatalogNameInput
{
    public function __construct(#[Assert\NotBlank] #[Assert\Length(max: 150)] public string $name)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['name'] ?? null) ? trim($payload['name']) : '');
    }
}
