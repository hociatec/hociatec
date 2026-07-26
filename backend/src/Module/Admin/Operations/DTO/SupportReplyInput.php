<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\DTO;

use App\Module\Support\Enum\SupportStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SupportReplyInput
{
    public function __construct(#[Assert\NotBlank] public string $message, #[Assert\Length(max: 255)] public ?string $subject, #[Assert\Choice(choices: [SupportStatus::NEW->value, SupportStatus::IN_PROGRESS->value, SupportStatus::WAITING_CUSTOMER->value, SupportStatus::RESOLVED->value, SupportStatus::REFUSED->value])] public ?string $status)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_string($p['message'] ?? null) ? trim($p['message']) : '', is_string($p['subject'] ?? null) ? trim($p['subject']) : null, is_string($p['status'] ?? null) ? trim($p['status']) : null);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_filter(['message' => $this->message, 'subject' => $this->subject, 'status' => $this->status], static fn (mixed $v): bool => null !== $v);
    }
}
