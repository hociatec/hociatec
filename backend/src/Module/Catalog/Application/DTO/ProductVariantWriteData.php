<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

final readonly class ProductVariantWriteData
{
    /** @param list<array<string, mixed>> $definitions */
    public function __construct(
        public ?string $group,
        public ?int $releaseYear,
        public ?string $storageCapacity,
        public ?string $memoryRam,
        public ?string $color,
        public array $definitions,
    ) {
    }
}
