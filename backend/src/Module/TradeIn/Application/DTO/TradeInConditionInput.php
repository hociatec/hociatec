<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\DTO;

final readonly class TradeInConditionInput
{
    public function __construct(
        public string $conditionGrade,
        public bool $functional,
        public bool $hasAccessories,
        public bool $hasProofOfPurchase,
        public string $description,
    ) {
    }
}
