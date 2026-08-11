<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

trait ProductGalleryMutationsTrait
{
    public function setImageFile(?object $imageFile): self
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile || null !== $this->gallery()->imageName()) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function setGalleryImageFile(int $position, ?object $file): self
    {
        match ($position) {
            0 => $this->changePrimaryGalleryImageFile($file),
            1 => $this->changeGalleryImage2File($file),
            2 => $this->changeGalleryImage3File($file),
            3 => $this->changeGalleryImage4File($file),
            default => throw new \InvalidArgumentException('Indice d\'image de galerie invalide.'),
        };

        return $this;
    }

    public function removeGalleryImage(int $position): self
    {
        match ($position) {
            0 => $this->imageFile = null,
            1 => $this->galleryImage2File = null,
            2 => $this->galleryImage3File = null,
            3 => $this->galleryImage4File = null,
            default => throw new \InvalidArgumentException('Indice d\'image de galerie invalide.'),
        };

        if ($this->gallery()->remove($position)) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    private function changePrimaryGalleryImageFile(?object $file): void
    {
        $this->imageFile = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->imageName());
    }

    private function changeGalleryImage2File(?object $file): void
    {
        $this->galleryImage2File = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->galleryImage2Name());
    }

    private function changeGalleryImage3File(?object $file): void
    {
        $this->galleryImage3File = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->galleryImage3Name());
    }

    private function changeGalleryImage4File(?object $file): void
    {
        $this->galleryImage4File = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->galleryImage4Name());
    }
}
