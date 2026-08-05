<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ProductWriteData
{
    /**
     * @param array<int, UploadedFile|null> $galleryFiles
     * @param list<int>                     $galleryToRemove
     * @param list<array<string, mixed>>    $variantDefinitions
     */
    public function __construct(
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

    public function toCreateCommand(): ProductWriteCommand
    {
        return $this->toCommand(null);
    }

    public function toUpdateCommand(Product $product): ProductWriteCommand
    {
        return $this->toCommand($product);
    }

    private function toCommand(?Product $product): ProductWriteCommand
    {
        return new ProductWriteCommand(
            $product,
            $this->name,
            $this->sku,
            $this->slug,
            $this->description,
            $this->shortDescription,
            $this->priceCents,
            $this->stock,
            $this->isPublished,
            $this->isFeaturedHome,
            $this->category,
            $this->galleryFiles,
            $this->imageAlt,
            $product instanceof Product ? $this->galleryToRemove : [],
            $product instanceof Product && $this->removeImage,
            $this->sellingType,
            $this->brand,
            $this->variantGroup,
            $this->releaseYear,
            $this->storageCapacity,
            $this->memoryRam,
            $this->color,
            $this->variantDefinitions,
            $this->discountEnabled,
            $this->discountType,
            $this->discountValue,
            $this->discountStartsAt,
            $this->discountEndsAt,
        );
    }
}
