<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

use App\Module\Catalog\Application\Query\ProductAdminCriteria;

final readonly class ProductAdminListQuery
{
    public int $page;
    public int $perPage;
    public ?string $categorySlug;
    public ?string $search;
    public ?bool $featured;
    public ?string $sellingType;
    public ?int $minPriceCents;
    public ?int $maxPriceCents;
    public bool $lowStock;
    public ?string $sort;

    /**
     * @param array{
     *   page:int,
     *   perPage:int,
     *   categorySlug:?string,
     *   search:?string,
     *   featured:?bool,
     *   sellingType:?string,
     *   minPriceCents:?int,
     *   maxPriceCents:?int,
     *   lowStock:bool,
     *   sort:?string
     * } $payload
     */
    public function __construct(
        ?array $payload = null,
    ) {
        $payload ??= [
            'page' => 1,
            'perPage' => 25,
            'categorySlug' => null,
            'search' => null,
            'featured' => null,
            'sellingType' => null,
            'minPriceCents' => null,
            'maxPriceCents' => null,
            'lowStock' => false,
            'sort' => null,
        ];
        $this->page = $payload['page'];
        $this->perPage = $payload['perPage'];
        $this->categorySlug = $payload['categorySlug'];
        $this->search = $payload['search'];
        $this->featured = $payload['featured'];
        $this->sellingType = $payload['sellingType'];
        $this->minPriceCents = $payload['minPriceCents'];
        $this->maxPriceCents = $payload['maxPriceCents'];
        $this->lowStock = $payload['lowStock'];
        $this->sort = $payload['sort'];
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function criteria(): ProductAdminCriteria
    {
        return new ProductAdminCriteria([
            'categorySlug' => $this->categorySlug,
            'search' => $this->search,
            'onlyFeatured' => $this->featured,
            'sellingType' => $this->sellingType,
            'minPriceCents' => $this->minPriceCents,
            'maxPriceCents' => $this->maxPriceCents,
            'lowStockOnly' => $this->lowStock,
            'sort' => $this->sort,
            'limit' => $this->perPage,
            'offset' => $this->offset(),
        ]);
    }

}
