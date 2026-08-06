<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\ValueObject;

abstract readonly class ProductAttributeText
{
    public ?string $value;

    final protected function __construct(?string $value)
    {
        $normalized = null !== $value ? trim($value) : null;
        $this->value = '' !== $normalized ? $normalized : null;
    }

    final public function value(): ?string
    {
        return $this->value;
    }
}
