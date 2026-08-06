<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInProductIdentity
{
    public function __construct(
        public string $category,
        public string $productName,
        public ?string $brand,
        public ?string $model,
        public ?string $serialNumber,
        public ?int $catalogProductId,
        public ?string $catalogProductName,
    ) {
        if ('' === trim($category)) {
            throw new \InvalidArgumentException('La catégorie de reprise est obligatoire.');
        }
        if ('' === trim($productName)) {
            throw new \InvalidArgumentException('Le nom du produit repris est obligatoire.');
        }
    }
}
