<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

final readonly class ProductCatalogCriteria
{
    public ?string $categorySlug;
    public ?string $search;
    public ?bool $onlyFeatured;
    public ?string $sellingType;
    public ?string $brand;
    /** @var array<string, string> */
    public array $attributeFilters;
    public ?string $storageCapacity;
    public ?string $memoryRam;
    public ?string $color;
    public ?int $minPriceCents;
    public ?int $maxPriceCents;
    public ?bool $inStockOnly;
    public ?string $sort;
    public ?int $limit;
    public ?int $offset;

    /**
     * @param array{
     *   categorySlug:?string,
     *   search:?string,
     *   onlyFeatured:?bool,
     *   sellingType:?string,
     *   brand:?string,
     *   attributeFilters?:array<string,string>,
     *   storageCapacity?:?string,
     *   memoryRam?:?string,
     *   color?:?string,
     *   minPriceCents:?int,
     *   maxPriceCents:?int,
     *   inStockOnly:?bool,
     *   sort:?string,
     *   limit:?int,
     *   offset:?int
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        /** @var array{
         *   categorySlug:?string,
         *   search:?string,
         *   onlyFeatured:?bool,
         *   sellingType:?string,
         *   brand:?string,
         *   attributeFilters:array<string,string>,
         *   minPriceCents:?int,
         *   maxPriceCents:?int,
         *   inStockOnly:?bool,
         *   sort:?string,
         *   limit:?int,
         *   offset:?int
         * } $data
         */
        $data = array_replace([
            'categorySlug' => null,
            'search' => null,
            'onlyFeatured' => null,
            'sellingType' => null,
            'brand' => null,
            'attributeFilters' => [],
            'storageCapacity' => null,
            'memoryRam' => null,
            'color' => null,
            'minPriceCents' => null,
            'maxPriceCents' => null,
            'inStockOnly' => null,
            'sort' => null,
            'limit' => null,
            'offset' => null,
        ], $payload ?? []);
        $this->categorySlug = $data['categorySlug'];
        $this->search = $data['search'];
        $this->onlyFeatured = $data['onlyFeatured'];
        $this->sellingType = $data['sellingType'];
        $this->brand = $data['brand'];
        $this->attributeFilters = is_array($data['attributeFilters'] ?? null) ? $data['attributeFilters'] : [];
        $this->storageCapacity = is_string($data['storageCapacity'] ?? null) ? $data['storageCapacity'] : ($this->attributeFilters['storage'] ?? null);
        $this->memoryRam = is_string($data['memoryRam'] ?? null) ? $data['memoryRam'] : ($this->attributeFilters['ram'] ?? null);
        $this->color = is_string($data['color'] ?? null) ? $data['color'] : ($this->attributeFilters['color'] ?? null);
        $this->minPriceCents = $data['minPriceCents'];
        $this->maxPriceCents = $data['maxPriceCents'];
        $this->inStockOnly = $data['inStockOnly'];
        $this->sort = $data['sort'];
        $this->limit = $data['limit'];
        $this->offset = $data['offset'];
    }

    public static function fromCatalogQuery(ProductCatalogQuery $query): self
    {
        return new self(
            [
                'categorySlug' => $query->categorySlug,
                'search' => $query->search,
                'onlyFeatured' => $query->onlyFeatured,
                'sellingType' => $query->sellingType,
                'brand' => $query->brand,
                'attributeFilters' => $query->attributeFilters,
                'storageCapacity' => $query->storageCapacity ?? ($query->attributeFilters['storage'] ?? null),
                'memoryRam' => $query->memoryRam ?? ($query->attributeFilters['ram'] ?? null),
                'color' => $query->color ?? ($query->attributeFilters['color'] ?? null),
                'minPriceCents' => $query->minPriceCents,
                'maxPriceCents' => $query->maxPriceCents,
                'inStockOnly' => $query->inStockOnly,
                'sort' => $query->sort,
                'limit' => $query->perPage,
                'offset' => $query->offset(),
            ],
        );
    }

    public function withoutSortAndPagination(): self
    {
        return new self(
            [
                'categorySlug' => $this->categorySlug,
                'search' => $this->search,
                'onlyFeatured' => $this->onlyFeatured,
                'sellingType' => $this->sellingType,
                'brand' => $this->brand,
                'attributeFilters' => $this->attributeFilters,
                'storageCapacity' => $this->storageCapacity,
                'memoryRam' => $this->memoryRam,
                'color' => $this->color,
                'minPriceCents' => $this->minPriceCents,
                'maxPriceCents' => $this->maxPriceCents,
                'inStockOnly' => $this->inStockOnly,
                'sort' => null,
                'limit' => null,
                'offset' => null,
            ],
        );
    }

    public function withoutPagination(): self
    {
        return new self(
            [
                'categorySlug' => $this->categorySlug,
                'search' => $this->search,
                'onlyFeatured' => $this->onlyFeatured,
                'sellingType' => $this->sellingType,
                'brand' => $this->brand,
                'attributeFilters' => $this->attributeFilters,
                'storageCapacity' => $this->storageCapacity,
                'memoryRam' => $this->memoryRam,
                'color' => $this->color,
                'minPriceCents' => $this->minPriceCents,
                'maxPriceCents' => $this->maxPriceCents,
                'inStockOnly' => $this->inStockOnly,
                'sort' => $this->sort,
                'limit' => null,
                'offset' => null,
            ],
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
            'attributeFilters' => $this->attributeFilters,
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
