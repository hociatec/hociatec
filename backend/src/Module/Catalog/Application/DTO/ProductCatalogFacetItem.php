<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

final readonly class ProductCatalogFacetItem
{
    public function __construct(
        public string $value,
        public int $count,
        public ?string $extra = null,
    ) {
    }

    /** @return array{value:string,count:int,extra:?string} */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'count' => $this->count,
            'extra' => $this->extra,
        ];
    }
}
