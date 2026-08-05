<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Application\DTO\ProductCoreWriteData;
use App\Module\Catalog\Application\DTO\ProductDiscountWriteData;
use App\Module\Catalog\Application\DTO\ProductGalleryWriteData;
use App\Module\Catalog\Application\DTO\ProductVariantWriteData;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductWriteData
{
    public function __construct(
        public ProductCoreWriteData $core,
        public ProductGalleryWriteData $gallery,
        public ProductVariantWriteData $variant,
        public ProductDiscountWriteData $discount,
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
        $gallery = $product instanceof Product
            ? $this->gallery
            : new ProductGalleryWriteData(files: $this->gallery->files);

        return $product instanceof Product
            ? ProductWriteCommand::forUpdate($product, $this->core, $gallery, $this->variant, $this->discount)
            : ProductWriteCommand::forCreate($this->core, $gallery, $this->variant, $this->discount);
    }
}
