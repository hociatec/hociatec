<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

/**
 * @implements \ArrayAccess<string, mixed>
 */
final readonly class StockMovementOutput implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array{
     *   id:?int,
     *   product:array{id:?int,name:string,sku:string},
     *   delta:int,
     *   stockBefore:int,
     *   stockAfter:int,
     *   reason:string,
     *   note:?string,
     *   actor:?string,
     *   createdAt:string,
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
        throw new \BadMethodCallException('StockMovementOutput is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('StockMovementOutput is immutable.');
    }

    /**
     * @return array{
     *   id:?int,
     *   product:array{id:?int,name:string,sku:string},
     *   delta:int,
     *   stockBefore:int,
     *   stockAfter:int,
     *   reason:string,
     *   note:?string,
     *   actor:?string,
     *   createdAt:string,
     * }
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * @return array{
     *   id:?int,
     *   product:array{id:?int,name:string,sku:string},
     *   delta:int,
     *   stockBefore:int,
     *   stockAfter:int,
     *   reason:string,
     *   note:?string,
     *   actor:?string,
     *   createdAt:string,
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
