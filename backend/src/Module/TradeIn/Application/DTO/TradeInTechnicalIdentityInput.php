<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\DTO;

final readonly class TradeInTechnicalIdentityInput
{
    public function __construct(
        public ?string $brand,
        public ?string $model,
        public ?string $serialNumber,
    ) {
    }
}
