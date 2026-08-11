<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

/**
 * @implements \ArrayAccess<string, mixed>
 */
final readonly class FulfillmentOrderOutput implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array{
     *   id:?int,
     *   number:string,
     *   status:string,
     *   statusLabel:string,
     *   customer:array{id:?int,name:string,email:string},
     *   totalPriceCents:int,
     *   shipping:array{name:?string,address:?string,postalCode:?string,city:?string},
     *   delivery:array{status:?string,statusLabel:string,carrier:?string,trackingNumber:?string,trackingUrl:?string},
     *   items:list<array{name:string,sku:string,quantity:int}>,
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
        throw new \BadMethodCallException('FulfillmentOrderOutput is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('FulfillmentOrderOutput is immutable.');
    }

    /**
     * @return array{
     *   id:?int,
     *   number:string,
     *   status:string,
     *   statusLabel:string,
     *   customer:array{id:?int,name:string,email:string},
     *   totalPriceCents:int,
     *   shipping:array{name:?string,address:?string,postalCode:?string,city:?string},
     *   delivery:array{status:?string,statusLabel:string,carrier:?string,trackingNumber:?string,trackingUrl:?string},
     *   items:list<array{name:string,sku:string,quantity:int}>,
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
     *   number:string,
     *   status:string,
     *   statusLabel:string,
     *   customer:array{id:?int,name:string,email:string},
     *   totalPriceCents:int,
     *   shipping:array{name:?string,address:?string,postalCode:?string,city:?string},
     *   delivery:array{status:?string,statusLabel:string,carrier:?string,trackingNumber:?string,trackingUrl:?string},
     *   items:list<array{name:string,sku:string,quantity:int}>,
     *   createdAt:string,
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
