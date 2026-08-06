<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

final readonly class ProductGalleryWriteData
{
    /**
     * @param array<int, object|null> $files
     * @param array<int, int|string>  $toRemove
     */
    public function __construct(
        public array $files,
        public array $toRemove = [],
        public bool $removeMainImage = false,
    ) {
    }
}
