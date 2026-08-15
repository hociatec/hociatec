<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

readonly class ProductCatalogQuery
{
    public int $page;
    public int $perPage;
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

    /**
     * @param array{
     *   page:int,
     *   perPage:int,
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
     *   sort:?string
     * } $payload
     */
    public function __construct(?array $payload = null)
    {
        $payload = array_replace([
            'page' => 1,
            'perPage' => 12,
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
        ], $payload ?? []);
        $this->page = $payload['page'];
        $this->perPage = $payload['perPage'];
        $this->categorySlug = $payload['categorySlug'];
        $this->search = $payload['search'];
        $this->onlyFeatured = $payload['onlyFeatured'];
        $this->sellingType = $payload['sellingType'];
        $this->brand = $payload['brand'];
        $this->attributeFilters = $payload['attributeFilters'];
        $this->storageCapacity = is_string($payload['storageCapacity']) ? $payload['storageCapacity'] : ($this->attributeFilters['storage'] ?? null);
        $this->memoryRam = is_string($payload['memoryRam']) ? $payload['memoryRam'] : ($this->attributeFilters['ram'] ?? null);
        $this->color = is_string($payload['color']) ? $payload['color'] : ($this->attributeFilters['color'] ?? null);
        $this->minPriceCents = $payload['minPriceCents'];
        $this->maxPriceCents = $payload['maxPriceCents'];
        $this->inStockOnly = $payload['inStockOnly'];
        $this->sort = $payload['sort'];
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
