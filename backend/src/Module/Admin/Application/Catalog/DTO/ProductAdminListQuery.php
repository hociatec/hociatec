<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

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
}
