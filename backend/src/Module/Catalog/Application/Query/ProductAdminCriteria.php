<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

final readonly class ProductAdminCriteria
{
    public ?string $categorySlug;
    public ?string $search;
    public ?bool $onlyFeatured;
    public ?string $sellingType;
    public ?int $minPriceCents;
    public ?int $maxPriceCents;
    public ?bool $lowStockOnly;
    public ?string $sort;
    public int $limit;
    public int $offset;

    /**
     * @param array{
     *   categorySlug:?string,
     *   search:?string,
     *   onlyFeatured:?bool,
     *   sellingType:?string,
     *   minPriceCents:?int,
     *   maxPriceCents:?int,
     *   lowStockOnly:?bool,
     *   sort:?string,
     *   limit:int,
     *   offset:int
     * } $payload
     */
    public function __construct(?array $payload = null)
    {
        $payload = array_replace([
            'categorySlug' => null,
            'search' => null,
            'onlyFeatured' => null,
            'sellingType' => null,
            'minPriceCents' => null,
            'maxPriceCents' => null,
            'lowStockOnly' => null,
            'sort' => null,
            'limit' => 100,
            'offset' => 0,
        ], $payload ?? []);
        $this->categorySlug = $payload['categorySlug'];
        $this->search = $payload['search'];
        $this->onlyFeatured = $payload['onlyFeatured'];
        $this->sellingType = $payload['sellingType'];
        $this->minPriceCents = $payload['minPriceCents'];
        $this->maxPriceCents = $payload['maxPriceCents'];
        $this->lowStockOnly = $payload['lowStockOnly'];
        $this->sort = $payload['sort'];
        $this->limit = $payload['limit'];
        $this->offset = $payload['offset'];
    }

    public function withoutSortAndPagination(): self
    {
        return new self(
            [
                'categorySlug' => $this->categorySlug,
                'search' => $this->search,
                'onlyFeatured' => $this->onlyFeatured,
                'sellingType' => $this->sellingType,
                'minPriceCents' => $this->minPriceCents,
                'maxPriceCents' => $this->maxPriceCents,
                'lowStockOnly' => $this->lowStockOnly,
                'sort' => null,
                'limit' => 100,
                'offset' => 0,
            ],
        );
    }
}
