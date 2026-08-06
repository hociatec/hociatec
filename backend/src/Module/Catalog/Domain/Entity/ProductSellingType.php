<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

enum ProductSellingType: string
{
    case Sale = 'sale';
    case Rental = 'rental';

    public static function fromInput(self|string $type): self
    {
        if ($type instanceof self) {
            return $type;
        }

        return self::tryFrom(strtolower(trim($type)))
            ?? throw new \InvalidArgumentException('Type de vente/location invalide.');
    }
}
