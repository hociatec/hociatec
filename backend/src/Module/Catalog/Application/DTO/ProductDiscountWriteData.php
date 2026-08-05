<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

final readonly class ProductDiscountWriteData
{
    public function __construct(
        public bool $enabled,
        public ?string $type,
        public ?int $value,
        public ?\DateTimeImmutable $startsAt,
        public ?\DateTimeImmutable $endsAt,
    ) {
    }
}
