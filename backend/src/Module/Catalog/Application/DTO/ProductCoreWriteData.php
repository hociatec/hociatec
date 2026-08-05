<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;

final readonly class ProductCoreWriteData
{
    public function __construct(
        public string $name,
        public string $sku,
        public ?string $slug,
        public string $description,
        public ?string $shortDescription,
        public int $priceCents,
        public int $stock,
        public bool $isPublished,
        public bool $isFeaturedHome,
        public Category $category,
        public ?string $imageAlt,
        public string $sellingType,
        public ?Brand $brand,
    ) {
    }
}
