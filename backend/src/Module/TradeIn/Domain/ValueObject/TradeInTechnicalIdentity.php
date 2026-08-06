<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInTechnicalIdentity
{
    public function __construct(
        public ?string $brand,
        public ?string $model,
        public ?string $serialNumber,
    ) {
    }
}
