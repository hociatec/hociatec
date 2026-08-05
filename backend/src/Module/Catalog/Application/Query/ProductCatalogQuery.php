<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

readonly class ProductCatalogQuery
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $categorySlug,
        public ?string $search,
        public ?bool $onlyFeatured,
        public ?string $sellingType,
        public ?string $brand,
        public ?string $storageCapacity,
        public ?string $memoryRam,
        public ?string $color,
        public ?int $minPriceCents,
        public ?int $maxPriceCents,
        public ?bool $inStockOnly,
        public ?string $sort,
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function criteria(): ProductCatalogCriteria
    {
        return ProductCatalogCriteria::fromCatalogQuery($this);
    }
}
