<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Writer;

use App\Module\Catalog\Domain\Entity\Product;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductGalleryUpdater
{
    /**
     * @param array<int, UploadedFile|null> $files
     * @param array<int, int|string>        $indexesToRemove
     */
    public function stage(Product $product, array $files, array $indexesToRemove): void
    {
        $removals = array_unique(array_map(static fn ($value): int => (int) $value, $indexesToRemove));

        foreach ($files as $index => $file) {
            $index = (int) $index;
            if ($index < 0 || $index > 3 || !$file instanceof UploadedFile) {
                continue;
            }

            $product->setGalleryImageFile($index, $file);
            $removals = array_values(array_filter(
                $removals,
                static fn (int $value): bool => $value !== $index,
            ));
        }

        foreach ($removals as $index) {
            if ($index >= 0 && $index <= 3) {
                $product->removeGalleryImage($index);
            }
        }
    }
}
