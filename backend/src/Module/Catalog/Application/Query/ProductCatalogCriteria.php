<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

final readonly class ProductCatalogCriteria
{
    public function __construct(
        public ?string $categorySlug = null,
        public ?string $search = null,
        public ?bool $onlyFeatured = null,
        public ?string $sellingType = null,
        public ?string $brand = null,
        public ?string $storageCapacity = null,
        public ?string $memoryRam = null,
        public ?string $color = null,
        public ?int $minPriceCents = null,
        public ?int $maxPriceCents = null,
        public ?bool $inStockOnly = null,
        public ?string $sort = null,
        public ?int $limit = null,
        public ?int $offset = null,
    ) {
    }

    public static function fromCatalogQuery(ProductCatalogQuery $query): self
    {
        return new self(
            categorySlug: $query->categorySlug,
            search: $query->search,
            onlyFeatured: $query->onlyFeatured,
            sellingType: $query->sellingType,
            brand: $query->brand,
            storageCapacity: $query->storageCapacity,
            memoryRam: $query->memoryRam,
            color: $query->color,
            minPriceCents: $query->minPriceCents,
            maxPriceCents: $query->maxPriceCents,
            inStockOnly: $query->inStockOnly,
            sort: $query->sort,
            limit: $query->perPage,
            offset: $query->offset(),
        );
    }

    public function withoutSortAndPagination(): self
    {
        return new self(
            categorySlug: $this->categorySlug,
            search: $this->search,
            onlyFeatured: $this->onlyFeatured,
            sellingType: $this->sellingType,
            brand: $this->brand,
            storageCapacity: $this->storageCapacity,
            memoryRam: $this->memoryRam,
            color: $this->color,
            minPriceCents: $this->minPriceCents,
            maxPriceCents: $this->maxPriceCents,
            inStockOnly: $this->inStockOnly,
        );
    }

    /** @return array<string, mixed> */
    public function cacheKeyPayload(): array
    {
        return [
            'categorySlug' => $this->categorySlug,
            'search' => $this->search,
            'onlyFeatured' => $this->onlyFeatured,
            'sellingType' => $this->sellingType,
            'brand' => $this->brand,
            'storageCapacity' => $this->storageCapacity,
            'memoryRam' => $this->memoryRam,
            'color' => $this->color,
            'minPriceCents' => $this->minPriceCents,
            'maxPriceCents' => $this->maxPriceCents,
            'inStockOnly' => $this->inStockOnly,
            'sort' => $this->sort,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }
}
