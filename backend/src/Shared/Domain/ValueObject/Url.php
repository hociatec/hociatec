<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final readonly class Url
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);
        if ('' === $value || false === filter_var($value, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL invalide.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
