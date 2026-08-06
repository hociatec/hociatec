<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

enum Currency: string
{
    case EUR = 'EUR';
    case USD = 'USD';

    public static function fromCode(string $code): self
    {
        return self::tryFrom(strtoupper(trim($code)))
            ?? throw new \InvalidArgumentException('Code monnaie invalide.');
    }
}
