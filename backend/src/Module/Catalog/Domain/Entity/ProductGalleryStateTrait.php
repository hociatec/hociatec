<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

trait ProductGalleryStateTrait
{
    private ?ProductGallery $gallery = null;

    private function gallery(): ProductGallery
    {
        if (!$this->gallery instanceof ProductGallery) {
            $fields = [
                'imageName' => &$this->imageName,
                'imageSize' => &$this->imageSize,
                'imageAlt' => &$this->imageAlt,
                'galleryImage2Name' => &$this->galleryImage2Name,
                'galleryImage2Size' => &$this->galleryImage2Size,
                'galleryImage3Name' => &$this->galleryImage3Name,
                'galleryImage3Size' => &$this->galleryImage3Size,
                'galleryImage4Name' => &$this->galleryImage4Name,
                'galleryImage4Size' => &$this->galleryImage4Size,
            ];
            $this->gallery = new ProductGallery($fields);
        }

        return $this->gallery;
    }

    private function touchWhenGalleryImageChanges(?object $file, ?string $existingName): void
    {
        if (null !== $file || null !== $existingName) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
}
