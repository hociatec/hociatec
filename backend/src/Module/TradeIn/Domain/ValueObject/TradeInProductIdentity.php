<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInProductIdentity
{
    public ?string $brand;
    public ?string $model;
    public ?string $serialNumber;
    public ?int $catalogProductId;
    public ?string $catalogProductName;

    public function __construct(
        public string $category,
        public string $productName,
        public ?TradeInTechnicalIdentity $technicalIdentity = null,
        public ?TradeInCatalogReference $catalogReference = null,
    ) {
        if ('' === trim($category)) {
            throw new \InvalidArgumentException('La catégorie de reprise est obligatoire.');
        }
        if ('' === trim($productName)) {
            throw new \InvalidArgumentException('Le nom du produit repris est obligatoire.');
        }

        $this->brand = $technicalIdentity?->brand;
        $this->model = $technicalIdentity?->model;
        $this->serialNumber = $technicalIdentity?->serialNumber;
        $this->catalogProductId = $catalogReference?->productId;
        $this->catalogProductName = $catalogReference?->productName;
    }
}
