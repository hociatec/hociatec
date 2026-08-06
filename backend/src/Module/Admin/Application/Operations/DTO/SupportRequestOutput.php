<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

final readonly class SupportRequestOutput implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array{
     *   id:int,
     *   status:string,
     *   statusLabel:string,
     *   reason:string,
     *   subject:string,
     *   message:string,
     *   internalNotes:?string,
     *   customer:array{id:?int,name:string,email:string},
     *   order:?array{id:?int,number:?string},
     *   createdAt:string,
     *   updatedAt:string,
     *   resolvedAt:?string,
     * } $payload
     */
    public function __construct(
        private array $payload,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists((string) $offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[(string) $offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('SupportRequestOutput is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('SupportRequestOutput is immutable.');
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
