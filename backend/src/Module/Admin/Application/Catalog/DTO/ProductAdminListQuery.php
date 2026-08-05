<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

use App\Module\Catalog\Application\Query\ProductAdminCriteria;

final readonly class ProductAdminListQuery
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $categorySlug,
        public ?string $search,
        public ?bool $featured,
        public ?string $sellingType,
        public ?int $minPriceCents,
        public ?int $maxPriceCents,
        public bool $lowStock,
        public ?string $sort,
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function criteria(): ProductAdminCriteria
    {
        return new ProductAdminCriteria(
            categorySlug: $this->categorySlug,
            search: $this->search,
            onlyFeatured: $this->featured,
            sellingType: $this->sellingType,
            minPriceCents: $this->minPriceCents,
            maxPriceCents: $this->maxPriceCents,
            lowStockOnly: $this->lowStock,
            sort: $this->sort,
            limit: $this->perPage,
            offset: $this->offset(),
        );
    }
}
