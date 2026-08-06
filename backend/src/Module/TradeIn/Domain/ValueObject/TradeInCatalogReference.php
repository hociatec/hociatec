<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInCatalogReference
{
    public function __construct(
        public ?int $productId,
        public ?string $productName,
    ) {
        if (null !== $productId && $productId <= 0) {
            throw new \InvalidArgumentException('La référence catalogue est invalide.');
        }
    }
}
