<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInProductSnapshot
{
    public function __construct(
        public string $category,
        public string $productName,
        public int $purchasePriceCents,
        public int $purchaseYear,
        public ?string $brand,
        public ?string $model,
        public ?string $serialNumber,
        public string $conditionGrade,
        public bool $functional,
        public bool $hasAccessories,
        public bool $hasProofOfPurchase,
        public string $description,
        public ?int $catalogProductId,
        public ?string $catalogProductName,
    ) {
    }
}
