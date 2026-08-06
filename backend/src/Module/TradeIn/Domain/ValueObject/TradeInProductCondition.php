<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInProductCondition
{
    public function __construct(
        public string $conditionGrade,
        public bool $functional,
        public bool $hasAccessories,
        public bool $hasProofOfPurchase,
        public string $description,
    ) {
        if ('' === trim($conditionGrade)) {
            throw new \InvalidArgumentException('L’état du produit repris est obligatoire.');
        }
        if ('' === trim($description)) {
            throw new \InvalidArgumentException('La description de reprise est obligatoire.');
        }
    }
}
