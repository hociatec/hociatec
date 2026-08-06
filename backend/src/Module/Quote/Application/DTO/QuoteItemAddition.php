<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\DTO;

final readonly class QuoteItemAddition
{
    public ?string $name;
    public ?int $unitPriceCents;
    public ?string $description;
    public ?string $unit;
    public int $quantity;
    public ?float $vatRate;
    public ?int $vatRateBps;
    public ?int $discountCents;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->name = $data['name'];
        $this->unitPriceCents = $data['unitPriceCents'];
        $this->description = $data['description'];
        $this->unit = $data['unit'];
        $this->quantity = (int) $data['quantity'];
        $this->vatRate = null !== $data['vatRate'] ? (float) $data['vatRate'] : null;
        $this->vatRateBps = $data['vatRateBps'];
        $this->discountCents = $data['discountCents'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['name'] ?? null) && '' !== trim($payload['name']) ? trim($payload['name']) : null,
            is_numeric($payload['unitPriceCents'] ?? null) ? max(0, (int) $payload['unitPriceCents']) : null,
            is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null,
            max(1, is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1),
            is_numeric($payload['vatRate'] ?? null) ? (float) $payload['vatRate'] : null,
            is_numeric($payload['vatRateBps'] ?? null) ? max(0, (int) $payload['vatRateBps']) : null,
            is_numeric($payload['discountCents'] ?? null) ? max(0, (int) $payload['discountCents']) : null,
        );
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['name', 'unitPriceCents', 'description', 'unit', 'quantity', 'vatRate', 'vatRateBps', 'discountCents'];
        $defaults = array_fill_keys($keys, null);
        $defaults['quantity'] = 1;
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $defaults[$keys[$index]] = $value;
            }
        }

        return array_replace($defaults, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
