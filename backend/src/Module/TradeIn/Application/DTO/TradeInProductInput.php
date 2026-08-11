<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\DTO;

final readonly class TradeInProductInput
{
    public function __construct(
        public string $category,
        public string $productName,
        public int $purchasePriceCents,
        public int $purchaseYear,
        public TradeInTechnicalIdentityInput $technicalIdentity,
        public TradeInConditionInput $condition,
        public ?int $catalogProductId,
    ) {
    }
}
