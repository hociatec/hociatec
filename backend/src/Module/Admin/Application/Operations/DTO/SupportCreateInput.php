<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SupportCreateInput
{
    public function __construct(#[Assert\Positive] public int $customerId, #[Assert\NotBlank] public string $subject, #[Assert\Length(max: 100)] public string $reason, public ?string $message, public ?string $internalNotes, #[Assert\Positive] public ?int $orderId)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_numeric($p['customerId'] ?? null) ? (int) $p['customerId'] : 0, is_string($p['subject'] ?? null) ? trim($p['subject']) : 'Demande SAV', is_string($p['reason'] ?? null) ? trim($p['reason']) : 'other', is_string($p['message'] ?? null) ? trim($p['message']) : null, is_string($p['internalNotes'] ?? null) ? trim($p['internalNotes']) : null, is_numeric($p['orderId'] ?? null) ? (int) $p['orderId'] : null);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return ['customerId' => $this->customerId, 'subject' => $this->subject, 'reason' => $this->reason, 'message' => $this->message, 'internalNotes' => $this->internalNotes, 'orderId' => $this->orderId];
    }
}
