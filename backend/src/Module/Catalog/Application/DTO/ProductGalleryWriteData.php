<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ProductGalleryWriteData
{
    /**
     * @param array<int, UploadedFile|null> $files
     * @param array<int, int|string>        $toRemove
     */
    public function __construct(
        public array $files,
        public array $toRemove = [],
        public bool $removeMainImage = false,
    ) {
    }
}
