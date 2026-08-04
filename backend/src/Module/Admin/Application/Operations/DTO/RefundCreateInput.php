<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefundCreateInput
{
    public function __construct(#[Assert\Positive] public int $orderId, #[Assert\Positive] public ?int $amountCents, #[Assert\Length(max: 2000)] public ?string $reason, #[Assert\Length(max: 2000)] public ?string $internalNotes, #[Assert\Positive] public ?int $paymentId, #[Assert\Length(min: 3, max: 3)] public string $currencyCode)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_numeric($p['orderId'] ?? null) ? (int) $p['orderId'] : 0, is_numeric($p['amountCents'] ?? null) ? (int) $p['amountCents'] : null, is_string($p['reason'] ?? null) ? trim($p['reason']) : null, is_string($p['internalNotes'] ?? null) ? trim($p['internalNotes']) : null, is_numeric($p['paymentId'] ?? null) ? (int) $p['paymentId'] : null, is_string($p['currencyCode'] ?? null) ? strtoupper(trim($p['currencyCode'])) : 'EUR');
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_filter(['orderId' => $this->orderId, 'amountCents' => $this->amountCents, 'reason' => $this->reason, 'internalNotes' => $this->internalNotes, 'paymentId' => $this->paymentId, 'currencyCode' => $this->currencyCode], static fn (mixed $v): bool => null !== $v);
    }
}
