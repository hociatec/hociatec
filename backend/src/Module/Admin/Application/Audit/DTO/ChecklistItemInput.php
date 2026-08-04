<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Audit\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChecklistItemInput
{
    public function __construct(public ?bool $isCompliant, #[Assert\Length(max: 2000)] public ?string $comment)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_bool($payload['isCompliant'] ?? null) ? $payload['isCompliant'] : null, is_string($payload['comment'] ?? null) ? trim($payload['comment']) : null);
    }
}
