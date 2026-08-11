<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

final readonly class ProductCatalogDiscountView
{
    public function __construct(
        public string $type,
        public int $value,
        public ?\DateTimeInterface $startsAt,
        public ?\DateTimeInterface $endsAt,
    ) {
    }
}
