<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInPurchase
{
    public function __construct(public int $priceCents, public int $year)
    {
        if ($priceCents < 0) {
            throw new \InvalidArgumentException('Le prix d’achat ne peut pas être négatif.');
        }
        if ($year < 1980 || $year > 2100) {
            throw new \InvalidArgumentException('L’année d’achat est invalide.');
        }
    }
}
