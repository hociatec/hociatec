<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

final readonly class ProductCatalogPriceRange
{
    public function __construct(
        public ?int $min,
        public ?int $max,
    ) {
    }

    /** @return array{min:int|null,max:int|null} */
    public function toArray(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
}
