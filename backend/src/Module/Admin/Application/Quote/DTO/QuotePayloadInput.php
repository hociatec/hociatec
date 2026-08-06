<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class QuotePayloadInput
{
    /** @var array<string,mixed> */
    public array $customer;
    #[Assert\Length(max: 20)]
    public string $status;
    #[Assert\PositiveOrZero]
    public int $discountCents;
    #[Assert\PositiveOrZero]
    public int $shippingCents;
    public ?string $conditions;
    public ?string $validFrom;
    public ?string $validUntil;
    /** @var list<array<string,mixed>> */
    public array $items;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->customer = $data['customer'];
        $this->status = (string) $data['status'];
        $this->discountCents = (int) $data['discountCents'];
        $this->shippingCents = (int) $data['shippingCents'];
        $this->conditions = $data['conditions'];
        $this->validFrom = $data['validFrom'];
        $this->validUntil = $data['validUntil'];
        $this->items = $data['items'];
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

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['customer', 'status', 'discountCents', 'shippingCents', 'conditions', 'validFrom', 'validUntil', 'items'];
        $defaults = array_fill_keys($keys, null);
        $defaults['customer'] = [];
        $defaults['status'] = 'draft';
        $defaults['discountCents'] = 0;
        $defaults['shippingCents'] = 0;
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
