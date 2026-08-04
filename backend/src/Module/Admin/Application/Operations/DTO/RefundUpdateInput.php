<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

use App\Module\Order\Domain\Enum\RefundStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefundUpdateInput
{
    public function __construct(#[Assert\Choice(choices: [RefundStatus::REQUESTED->value, RefundStatus::APPROVED->value, RefundStatus::REJECTED->value, RefundStatus::PROCESSING->value, RefundStatus::PROCESSED->value])] public ?string $status, public ?string $stripeRefundId, #[Assert\Length(max: 2000)] public ?string $internalNotes)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_string($p['status'] ?? null) ? trim($p['status']) : null, is_string($p['stripeRefundId'] ?? null) ? trim($p['stripeRefundId']) : null, is_string($p['internalNotes'] ?? null) ? trim($p['internalNotes']) : null);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_filter(['status' => $this->status, 'stripeRefundId' => $this->stripeRefundId, 'internalNotes' => $this->internalNotes], static fn (mixed $v): bool => null !== $v);
    }
}
