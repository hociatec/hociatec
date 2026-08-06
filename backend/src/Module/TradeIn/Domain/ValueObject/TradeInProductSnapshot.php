<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInProductSnapshot
{
    public string $category;
    public string $productName;
    public int $purchasePriceCents;
    public int $purchaseYear;
    public ?string $brand;
    public ?string $model;
    public ?string $serialNumber;
    public string $conditionGrade;
    public bool $functional;
    public bool $hasAccessories;
    public bool $hasProofOfPurchase;
    public string $description;
    public ?int $catalogProductId;
    public ?string $catalogProductName;

    public function __construct(
        public TradeInProductIdentity $identity,
        public TradeInPurchase $purchase,
        public TradeInProductCondition $condition,
    ) {
        $this->category = $identity->category;
        $this->productName = $identity->productName;
        $this->purchasePriceCents = $purchase->priceCents;
        $this->purchaseYear = $purchase->year;
        $this->brand = $identity->brand;
        $this->model = $identity->model;
        $this->serialNumber = $identity->serialNumber;
        $this->conditionGrade = $condition->conditionGrade;
        $this->functional = $condition->functional;
        $this->hasAccessories = $condition->hasAccessories;
        $this->hasProofOfPurchase = $condition->hasProofOfPurchase;
        $this->description = $condition->description;
        $this->catalogProductId = $identity->catalogProductId;
        $this->catalogProductName = $identity->catalogProductName;
    }
}
