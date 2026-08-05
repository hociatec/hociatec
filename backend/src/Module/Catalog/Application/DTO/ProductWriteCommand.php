<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ProductWriteCommand
{
    /**
     * @param array<int, UploadedFile|null> $galleryFiles
     * @param array<int, int|string>        $galleryToRemove
     * @param list<array<string, mixed>>    $variantDefinitions
     */
    public function __construct(
        public ?Product $product,
        public string $name,
        public string $sku,
        public ?string $slug,
        public string $description,
        public ?string $shortDescription,
        public int $priceCents,
        public int $stock,
        public bool $isPublished,
        public bool $isFeaturedHome,
        public Category $category,
        public array $galleryFiles,
        public ?string $imageAlt,
        public array $galleryToRemove,
        public bool $removeImage,
        public string $sellingType,
        public ?Brand $brand,
        public ?string $variantGroup,
        public ?int $releaseYear,
        public ?string $storageCapacity,
        public ?string $memoryRam,
        public ?string $color,
        public array $variantDefinitions,
        public bool $discountEnabled,
        public ?string $discountType,
        public ?int $discountValue,
        public ?\DateTimeImmutable $discountStartsAt,
        public ?\DateTimeImmutable $discountEndsAt,
    ) {
    }
}
