<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Appointment\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class WorkingDaysInput
{
    /** @param list<array<string,mixed>> $days */
    public function __construct(#[Assert\Count(min: 1, max: 7)] public array $days)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_array($p['days'] ?? null) ? array_values(array_filter($p['days'], static fn (mixed $v): bool => is_array($v))) : []);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return ['days' => $this->days];
    }
}
