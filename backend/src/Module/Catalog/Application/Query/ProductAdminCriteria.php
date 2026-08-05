<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

final readonly class ProductAdminCriteria
{
    public function __construct(
        public ?string $categorySlug = null,
        public ?string $search = null,
        public ?bool $onlyFeatured = null,
        public ?string $sellingType = null,
        public ?int $minPriceCents = null,
        public ?int $maxPriceCents = null,
        public ?bool $lowStockOnly = null,
        public ?string $sort = null,
        public int $limit = 100,
        public int $offset = 0,
    ) {
    }

    public function withoutSortAndPagination(): self
    {
        return new self(
            categorySlug: $this->categorySlug,
            search: $this->search,
            onlyFeatured: $this->onlyFeatured,
            sellingType: $this->sellingType,
            minPriceCents: $this->minPriceCents,
            maxPriceCents: $this->maxPriceCents,
            lowStockOnly: $this->lowStockOnly,
        );
    }
}
