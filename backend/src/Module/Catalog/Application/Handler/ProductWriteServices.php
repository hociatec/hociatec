<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\Factory\ProductVariantBatchCreator;
use App\Module\Catalog\Application\Workflow\ProductVariantService;
use App\Module\Catalog\Application\Writer\ProductAttributeWriter;
use App\Module\Catalog\Application\Writer\ProductDiscountApplicator;
use App\Module\Catalog\Application\Writer\ProductGalleryUpdater;

final readonly class ProductWriteServices
{
    public function __construct(
        public ProductVariantService $variants,
        public ProductVariantBatchCreator $variantBatch,
        public ProductGalleryUpdater $gallery,
        public ProductDiscountApplicator $discounts,
        public ProductAttributeWriter $attributes,
    ) {
    }
}
