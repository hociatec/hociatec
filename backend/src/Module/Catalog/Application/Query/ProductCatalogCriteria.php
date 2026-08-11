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
     *   storageCapacity:?string,
     *   memoryRam:?string,
     *   color:?string,
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
         *   storageCapacity:?string,
         *   memoryRam:?string,
         *   color:?string,
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
        $this->storageCapacity = $data['storageCapacity'];
        $this->memoryRam = $data['memoryRam'];
        $this->color = $data['color'];
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
                'storageCapacity' => $query->storageCapacity,
                'memoryRam' => $query->memoryRam,
                'color' => $query->color,
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
