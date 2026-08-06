<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\ValueObject;

final readonly class ProductColor extends ProductAttributeText
{
    public static function fromNullable(?string $value): self
    {
        return new self($value);
    }
}
