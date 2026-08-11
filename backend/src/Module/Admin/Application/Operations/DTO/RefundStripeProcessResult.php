<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

/**
 * @implements \ArrayAccess<string, mixed>
 */
final readonly class RefundStripeProcessResult implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array{item:RefundOutput,stripeRefund:array<string,mixed>} $payload
     */
    public function __construct(
        private array $payload,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists((string) $offset, $this->payload);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->payload[(string) $offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('RefundStripeProcessResult is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('RefundStripeProcessResult is immutable.');
    }

    /**
     * @return array{item:array<string,mixed>,stripeRefund:array<string,mixed>}
     */
    public function toArray(): array
    {
        return [
            'item' => $this->payload['item']->toArray(),
            'stripeRefund' => $this->payload['stripeRefund'],
        ];
    }

    /**
     * @return array{item:array<string,mixed>,stripeRefund:array<string,mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
