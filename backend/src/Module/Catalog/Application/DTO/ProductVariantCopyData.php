<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantCopyData
{
    public function __construct(
        public Product $template,
        public string $baseName,
        public string $baseSku,
        public ?string $baseSlug,
        public string $variantGroup,
        public ?string $color,
        public ?string $storageCapacity,
        public int $stock,
        public int $position,
    ) {
    }
}
