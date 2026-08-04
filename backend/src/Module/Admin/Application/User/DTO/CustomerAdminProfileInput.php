<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CustomerAdminProfileInput
{
    /** @param list<string> $adminTags */
    public function __construct(#[Assert\Length(max: 5000)] public string $adminNotes, public array $adminTags)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        $tags = is_array($p['adminTags'] ?? null) ? array_values(array_filter(array_map(static fn (mixed $v): string => is_scalar($v) ? trim((string) $v) : '', $p['adminTags']), static fn (string $v): bool => '' !== $v)) : [];

        return new self(is_string($p['adminNotes'] ?? null) ? trim($p['adminNotes']) : '', $tags);
    }
}
