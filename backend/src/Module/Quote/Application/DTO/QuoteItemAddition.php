<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\DTO;

use App\Shared\Domain\ValueObject\DecimalNumber;

final readonly class QuoteItemAddition
{
    public ?string $name;
    public ?int $unitPriceCents;
    public ?string $description;
    public ?string $unit;
    public int $quantity;
    public ?string $vatRate;
    public ?int $vatRateBps;
    public ?int $discountCents;

    /**
     * @param array{
     *   name?: ?string,
     *   unitPriceCents?: ?int,
     *   description?: ?string,
     *   unit?: ?string,
     *   quantity?: int,
     *   vatRate?: ?string,
     *   vatRateBps?: ?int,
     *   discountCents?: ?int
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'name' => null,
            'unitPriceCents' => null,
            'description' => null,
            'unit' => null,
            'quantity' => 1,
            'vatRate' => null,
            'vatRateBps' => null,
            'discountCents' => null,
        ], $payload ?? []);
        $this->name = $data['name'];
        $this->unitPriceCents = $data['unitPriceCents'];
        $this->description = $data['description'];
        $this->unit = $data['unit'];
        $this->quantity = (int) $data['quantity'];
        $this->vatRate = is_scalar($data['vatRate']) ? trim((string) $data['vatRate']) : null;
        $this->vatRateBps = $data['vatRateBps'];
        $this->discountCents = $data['discountCents'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self([
            'name' => is_string($payload['name'] ?? null) && '' !== trim($payload['name']) ? trim($payload['name']) : null,
            'unitPriceCents' => is_numeric($payload['unitPriceCents'] ?? null) ? max(0, (int) $payload['unitPriceCents']) : null,
            'description' => is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            'unit' => is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null,
            'quantity' => max(1, is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1),
            'vatRate' => null !== DecimalNumber::toScaledInt($payload['vatRate'] ?? null, 2)
                ? trim((string) $payload['vatRate'])
                : null,
            'vatRateBps' => is_numeric($payload['vatRateBps'] ?? null) ? max(0, (int) $payload['vatRateBps']) : null,
            'discountCents' => is_numeric($payload['discountCents'] ?? null) ? max(0, (int) $payload['discountCents']) : null,
        ]);
    }
}
