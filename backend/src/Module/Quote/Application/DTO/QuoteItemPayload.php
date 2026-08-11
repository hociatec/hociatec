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

    /**
     * @param array{
     *   name?: string,
     *   productId?: ?int,
     *   serviceId?: ?int,
     *   unitPriceCents?: ?int,
     *   description?: ?string,
     *   unit?: ?string,
     *   quantity?: int,
     *   vatRateBps?: int,
     *   discountCents?: int,
     *   type?: ?string
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'name' => '',
            'productId' => null,
            'serviceId' => null,
            'unitPriceCents' => null,
            'description' => null,
            'unit' => null,
            'quantity' => 1,
            'vatRateBps' => 0,
            'discountCents' => 0,
            'type' => null,
        ], $payload ?? []);
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
        return new self([
            'name' => is_string($payload['name'] ?? null) ? trim($payload['name']) : '',
            'productId' => is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : null,
            'serviceId' => is_numeric($payload['serviceId'] ?? null) ? (int) $payload['serviceId'] : null,
            'unitPriceCents' => is_numeric($payload['unitPriceCents'] ?? null) ? (int) $payload['unitPriceCents'] : null,
            'description' => is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            'unit' => is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null,
            'quantity' => max(1, is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1),
            'vatRateBps' => max(0, is_numeric($payload['vatRateBps'] ?? null) ? (int) $payload['vatRateBps'] : 0),
            'discountCents' => max(0, is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : 0),
            'type' => is_string($payload['type'] ?? null) ? trim($payload['type']) : null,
        ]);
    }
}
