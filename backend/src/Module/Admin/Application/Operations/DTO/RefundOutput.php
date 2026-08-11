<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

/**
 * @implements \ArrayAccess<string, mixed>
 */
final readonly class RefundOutput implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array{
     *   id:?int,
     *   order:array{id:?int,number:string},
     *   paymentId:?int,
     *   amountCents:int,
     *   currencyCode:string,
     *   status:string,
     *   reason:?string,
     *   internalNotes:?string,
     *   stripeRefundId:?string,
     *   createdAt:string,
     *   updatedAt:string,
     * } $payload
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
        throw new \BadMethodCallException('RefundOutput is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('RefundOutput is immutable.');
    }

    /**
     * @return array{
     *   id:?int,
     *   order:array{id:?int,number:string},
     *   paymentId:?int,
     *   amountCents:int,
     *   currencyCode:string,
     *   status:string,
     *   reason:?string,
     *   internalNotes:?string,
     *   stripeRefundId:?string,
     *   createdAt:string,
     *   updatedAt:string,
     * }
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * @return array{
     *   id:?int,
     *   order:array{id:?int,number:string},
     *   paymentId:?int,
     *   amountCents:int,
     *   currencyCode:string,
     *   status:string,
     *   reason:?string,
     *   internalNotes:?string,
     *   stripeRefundId:?string,
     *   createdAt:string,
     *   updatedAt:string,
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
