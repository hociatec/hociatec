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

    /**
     * @param array{
     *   customer?: array<string,mixed>,
     *   status?: string,
     *   discountCents?: int,
     *   shippingCents?: int,
     *   conditions?: ?string,
     *   validFrom?: ?string,
     *   validUntil?: ?string,
     *   items?: list<array<string,mixed>>
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'customer' => [],
            'status' => 'draft',
            'discountCents' => 0,
            'shippingCents' => 0,
            'conditions' => null,
            'validFrom' => null,
            'validUntil' => null,
            'items' => [],
        ], $payload ?? []);
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
        return new self([
            'customer' => is_array($p['customer'] ?? null) ? $p['customer'] : [],
            'status' => is_string($p['status'] ?? null) ? trim($p['status']) : 'draft',
            'discountCents' => is_numeric($p['discountCents'] ?? null) ? (int) $p['discountCents'] : 0,
            'shippingCents' => is_numeric($p['shippingCents'] ?? null) ? (int) $p['shippingCents'] : 0,
            'conditions' => is_string($p['conditions'] ?? null) ? trim($p['conditions']) : null,
            'validFrom' => is_string($p['validFrom'] ?? null) ? trim($p['validFrom']) : null,
            'validUntil' => is_string($p['validUntil'] ?? null) ? trim($p['validUntil']) : null,
            'items' => is_array($p['items'] ?? null) ? array_values(array_filter($p['items'], static fn (mixed $v): bool => is_array($v))) : [],
        ]);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return ['customer' => $this->customer, 'status' => $this->status, 'discountCents' => $this->discountCents, 'shippingCents' => $this->shippingCents, 'conditions' => $this->conditions, 'validFrom' => $this->validFrom, 'validUntil' => $this->validUntil, 'items' => $this->items];
    }

}
