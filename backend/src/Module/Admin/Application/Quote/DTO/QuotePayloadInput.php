<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class QuotePayloadInput
{
    /**
     * @param array<string,mixed>       $customer
     * @param list<array<string,mixed>> $items
     */
    public function __construct(public array $customer, #[Assert\Length(max: 20)] public string $status, #[Assert\PositiveOrZero] public int $discountCents, #[Assert\PositiveOrZero] public int $shippingCents, public ?string $conditions, public ?string $validFrom, public ?string $validUntil, public array $items)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_array($p['customer'] ?? null) ? $p['customer'] : [], is_string($p['status'] ?? null) ? trim($p['status']) : 'draft', is_numeric($p['discountCents'] ?? null) ? (int) $p['discountCents'] : 0, is_numeric($p['shippingCents'] ?? null) ? (int) $p['shippingCents'] : 0, is_string($p['conditions'] ?? null) ? trim($p['conditions']) : null, is_string($p['validFrom'] ?? null) ? trim($p['validFrom']) : null, is_string($p['validUntil'] ?? null) ? trim($p['validUntil']) : null, is_array($p['items'] ?? null) ? array_values(array_filter($p['items'], static fn (mixed $v): bool => is_array($v))) : []);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return ['customer' => $this->customer, 'status' => $this->status, 'discountCents' => $this->discountCents, 'shippingCents' => $this->shippingCents, 'conditions' => $this->conditions, 'validFrom' => $this->validFrom, 'validUntil' => $this->validUntil, 'items' => $this->items];
    }
}
