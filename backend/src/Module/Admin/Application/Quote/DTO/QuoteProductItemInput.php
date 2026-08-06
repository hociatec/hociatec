<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class QuoteProductItemInput
{
    #[Assert\Positive]
    public int $productId;
    #[Assert\Positive]
    public int $quantity;
    public ?string $name;
    public ?string $description;
    public ?string $unit;
    #[Assert\PositiveOrZero]
    public ?int $unitPriceCents;
    #[Assert\PositiveOrZero]
    public ?int $discountCents;
    #[Assert\PositiveOrZero]
    public ?float $vatRate;
    #[Assert\PositiveOrZero]
    public ?int $vatRateBps;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->productId = (int) $data['productId'];
        $this->quantity = (int) $data['quantity'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->unit = $data['unit'];
        $this->unitPriceCents = $data['unitPriceCents'];
        $this->discountCents = $data['discountCents'];
        $this->vatRate = null !== $data['vatRate'] ? (float) $data['vatRate'] : null;
        $this->vatRateBps = $data['vatRateBps'];
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

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['productId', 'quantity', 'name', 'description', 'unit', 'unitPriceCents', 'discountCents', 'vatRate', 'vatRateBps'];
        $defaults = array_fill_keys($keys, null);
        $defaults['productId'] = 0;
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
