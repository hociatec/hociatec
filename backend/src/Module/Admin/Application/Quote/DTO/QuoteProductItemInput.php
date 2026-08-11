<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\DTO;

use App\Shared\Domain\ValueObject\DecimalNumber;
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
    public ?string $vatRate;
    #[Assert\PositiveOrZero]
    public ?int $vatRateBps;

    /**
     * @param array{
     *   productId?: int,
     *   quantity?: int,
     *   name?: ?string,
     *   description?: ?string,
     *   unit?: ?string,
     *   unitPriceCents?: ?int,
     *   discountCents?: ?int,
     *   vatRate?: ?string,
     *   vatRateBps?: ?int
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'productId' => 0,
            'quantity' => 1,
            'name' => null,
            'description' => null,
            'unit' => null,
            'unitPriceCents' => null,
            'discountCents' => null,
            'vatRate' => null,
            'vatRateBps' => null,
        ], $payload ?? []);
        $this->productId = (int) $data['productId'];
        $this->quantity = (int) $data['quantity'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->unit = $data['unit'];
        $this->unitPriceCents = $data['unitPriceCents'];
        $this->discountCents = $data['discountCents'];
        $this->vatRate = is_scalar($data['vatRate']) ? trim((string) $data['vatRate']) : null;
        $this->vatRateBps = $data['vatRateBps'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self([
            'productId' => is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : 0,
            'quantity' => is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1,
            'name' => is_string($payload['name'] ?? null) ? trim($payload['name']) : null,
            'description' => is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            'unit' => is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null,
            'unitPriceCents' => is_numeric($payload['unitPriceCents'] ?? null) ? (int) $payload['unitPriceCents'] : null,
            'discountCents' => is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : null,
            'vatRate' => null !== DecimalNumber::toScaledInt($payload['vatRate'] ?? null, 2)
                ? trim((string) $payload['vatRate'])
                : null,
            'vatRateBps' => is_numeric($payload['vatRateBps'] ?? null) ? (int) $payload['vatRateBps'] : null,
        ]);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_filter(['name' => $this->name, 'description' => $this->description, 'unit' => $this->unit, 'quantity' => $this->quantity, 'unitPriceCents' => $this->unitPriceCents, 'discountCents' => $this->discountCents, 'vatRate' => $this->vatRate, 'vatRateBps' => $this->vatRateBps], static fn (mixed $value): bool => null !== $value);
    }

}
