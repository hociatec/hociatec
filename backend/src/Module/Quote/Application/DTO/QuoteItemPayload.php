<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\DTO;

final readonly class QuoteItemPayload
{
    public string $name;
    public ?int $productId;
    public ?int $serviceId;
    public ?int $unitPriceCents;
    public ?string $description;
    public ?string $unit;
    public int $quantity;
    public int $vatRateBps;
    public int $discountCents;
    public ?string $type;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->name = (string) $data['name'];
        $this->productId = $data['productId'];
        $this->serviceId = $data['serviceId'];
        $this->unitPriceCents = $data['unitPriceCents'];
        $this->description = $data['description'];
        $this->unit = $data['unit'];
        $this->quantity = (int) $data['quantity'];
        $this->vatRateBps = (int) $data['vatRateBps'];
        $this->discountCents = (int) $data['discountCents'];
        $this->type = $data['type'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['name'] ?? null) ? trim($payload['name']) : '',
            is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : null,
            is_numeric($payload['serviceId'] ?? null) ? (int) $payload['serviceId'] : null,
            is_numeric($payload['unitPriceCents'] ?? null) ? (int) $payload['unitPriceCents'] : null,
            is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null,
            max(1, is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1),
            max(0, is_numeric($payload['vatRateBps'] ?? null) ? (int) $payload['vatRateBps'] : 0),
            max(0, is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : 0),
            is_string($payload['type'] ?? null) ? trim($payload['type']) : null,
        );
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['name', 'productId', 'serviceId', 'unitPriceCents', 'description', 'unit', 'quantity', 'vatRateBps', 'discountCents', 'type'];
        $defaults = array_fill_keys($keys, null);
        $defaults['name'] = '';
        $defaults['quantity'] = 1;
        $defaults['vatRateBps'] = 0;
        $defaults['discountCents'] = 0;
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
