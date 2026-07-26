<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class QuoteProductItemInput
{
    public function __construct(#[Assert\Positive] public int $productId, #[Assert\Positive] public int $quantity = 1, public ?string $name = null, public ?string $description = null, public ?string $unit = null, #[Assert\PositiveOrZero] public ?int $unitPriceCents = null, #[Assert\PositiveOrZero] public ?int $discountCents = null, #[Assert\PositiveOrZero] public ?float $vatRate = null, #[Assert\PositiveOrZero] public ?int $vatRateBps = null)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : 0, is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1, is_string($payload['name'] ?? null) ? trim($payload['name']) : null, is_string($payload['description'] ?? null) ? trim($payload['description']) : null, is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null, is_numeric($payload['unitPriceCents'] ?? null) ? (int) $payload['unitPriceCents'] : null, is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : null, is_numeric($payload['vatRate'] ?? null) ? (float) $payload['vatRate'] : null, is_numeric($payload['vatRateBps'] ?? null) ? (int) $payload['vatRateBps'] : null);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_filter(['name' => $this->name, 'description' => $this->description, 'unit' => $this->unit, 'quantity' => $this->quantity, 'unitPriceCents' => $this->unitPriceCents, 'discountCents' => $this->discountCents, 'vatRate' => $this->vatRate, 'vatRateBps' => $this->vatRateBps], static fn (mixed $value): bool => null !== $value);
    }
}
