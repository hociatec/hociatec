<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\DTO\ProductGalleryWriteData;

final readonly class ProductWriteGalleryPlan
{
    /** @return list<int> */
    public function removals(ProductGalleryWriteData $gallery): array
    {
        $toRemove = $gallery->toRemove;
        if ($gallery->removeMainImage) {
            $toRemove[] = 0;
        }

        return $toRemove;
    }
}
