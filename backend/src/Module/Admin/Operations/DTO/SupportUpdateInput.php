<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\DTO;

use App\Module\Support\Enum\SupportStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SupportUpdateInput
{
    public function __construct(#[Assert\Choice(choices: [SupportStatus::NEW->value, SupportStatus::IN_PROGRESS->value, SupportStatus::WAITING_CUSTOMER->value, SupportStatus::RESOLVED->value, SupportStatus::REFUSED->value])] public ?string $status, #[Assert\Length(max: 2000)] public ?string $internalNotes, #[Assert\Length(max: 255)] public ?string $subject)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_string($p['status'] ?? null) ? trim($p['status']) : null, is_string($p['internalNotes'] ?? null) ? trim($p['internalNotes']) : null, is_string($p['subject'] ?? null) ? trim($p['subject']) : null);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_filter(['status' => $this->status, 'internalNotes' => $this->internalNotes, 'subject' => $this->subject], static fn (mixed $v): bool => null !== $v);
    }
}
