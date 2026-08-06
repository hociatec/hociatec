<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\DTO;

use App\Shared\Domain\ValueObject\Money;

final readonly class QuotePayload
{
    /** @var array<string,mixed> */
    public array $customer;
    public string $status;
    public Money $discount;
    public Money $shipping;
    public ?string $conditions;
    public ?string $validFrom;
    public ?string $validUntil;
    /** @var list<QuoteItemPayload> */
    public array $items;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->customer = $data['customer'];
        $this->status = (string) $data['status'];
        $this->discount = $data['discount'];
        $this->shipping = $data['shipping'];
        $this->conditions = $data['conditions'];
        $this->validFrom = $data['validFrom'];
        $this->validUntil = $data['validUntil'];
        $this->items = $data['items'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_array($payload['customer'] ?? null) ? $payload['customer'] : [],
            is_string($payload['status'] ?? null) ? trim($payload['status']) : 'draft',
            Money::fromCents(max(0, is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : 0)),
            Money::fromCents(max(0, is_numeric($payload['shippingCents'] ?? null) ? (int) $payload['shippingCents'] : 0)),
            is_string($payload['conditions'] ?? null) ? trim($payload['conditions']) : null,
            is_string($payload['validFrom'] ?? null) ? trim($payload['validFrom']) : null,
            is_string($payload['validUntil'] ?? null) ? trim($payload['validUntil']) : null,
            is_array($payload['items'] ?? null)
                ? array_values(array_map(
                    static fn (mixed $item): QuoteItemPayload => QuoteItemPayload::fromArray(is_array($item) ? $item : []),
                    $payload['items'],
                ))
                : [],
        );
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['customer', 'status', 'discount', 'shipping', 'conditions', 'validFrom', 'validUntil', 'items'];
        $defaults = array_fill_keys($keys, null);
        $defaults['customer'] = [];
        $defaults['status'] = 'draft';
        $defaults['discount'] = Money::fromCents(0);
        $defaults['shipping'] = Money::fromCents(0);
        $defaults['items'] = [];
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
