<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductWriteCommand
{
    private function __construct(
        public ?Product $product,
        public ProductCoreWriteData $core,
        public ProductGalleryWriteData $gallery,
        public ProductVariantWriteData $variant,
        public ProductDiscountWriteData $discount,
    ) {
    }

    public static function forCreate(
        ProductCoreWriteData $core,
        ProductGalleryWriteData $gallery,
        ProductVariantWriteData $variant,
        ProductDiscountWriteData $discount,
    ): self {
        return new self(null, $core, $gallery, $variant, $discount);
    }

    public static function forUpdate(
        Product $product,
        ProductCoreWriteData $core,
        ProductGalleryWriteData $gallery,
        ProductVariantWriteData $variant,
        ProductDiscountWriteData $discount,
    ): self {
        return new self($product, $core, $gallery, $variant, $discount);
    }
}
