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
     * } $payload
     */
    public function __construct(?array $payload = null)
    {
        $payload = array_replace([
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
        $this->categorySlug = $payload['categorySlug'];
        $this->search = $payload['search'];
        $this->onlyFeatured = $payload['onlyFeatured'];
        $this->sellingType = $payload['sellingType'];
        $this->brand = $payload['brand'];
        $this->storageCapacity = $payload['storageCapacity'];
        $this->memoryRam = $payload['memoryRam'];
        $this->color = $payload['color'];
        $this->minPriceCents = $payload['minPriceCents'];
        $this->maxPriceCents = $payload['maxPriceCents'];
        $this->inStockOnly = $payload['inStockOnly'];
        $this->sort = $payload['sort'];
        $this->limit = $payload['limit'];
        $this->offset = $payload['offset'];
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
