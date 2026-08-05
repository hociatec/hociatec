<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

final class ProductGallery
{
    private ?string $imageName = null;
    private ?int $imageSize = null;
    private ?string $imageAlt = null;
    private ?string $galleryImage2Name = null;
    private ?int $galleryImage2Size = null;
    private ?string $galleryImage3Name = null;
    private ?int $galleryImage3Size = null;
    private ?string $galleryImage4Name = null;
    private ?int $galleryImage4Size = null;

    public function __construct(
        ?string &$imageName,
        ?int &$imageSize,
        ?string &$imageAlt,
        ?string &$galleryImage2Name,
        ?int &$galleryImage2Size,
        ?string &$galleryImage3Name,
        ?int &$galleryImage3Size,
        ?string &$galleryImage4Name,
        ?int &$galleryImage4Size,
    ) {
        $this->imageName = &$imageName;
        $this->imageSize = &$imageSize;
        $this->imageAlt = &$imageAlt;
        $this->galleryImage2Name = &$galleryImage2Name;
        $this->galleryImage2Size = &$galleryImage2Size;
        $this->galleryImage3Name = &$galleryImage3Name;
        $this->galleryImage3Size = &$galleryImage3Size;
        $this->galleryImage4Name = &$galleryImage4Name;
        $this->galleryImage4Size = &$galleryImage4Size;
    }

    public function imageName(): ?string
    {
        return $this->imageName;
    }

    public function changeImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function imageSize(): ?int
    {
        return $this->imageSize;
    }

    public function changeImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function imageAlt(): ?string
    {
        return $this->imageAlt;
    }

    public function changeImageAlt(?string $imageAlt): void
    {
        $this->imageAlt = $imageAlt;
    }

    public function galleryImage2Name(): ?string
    {
        return $this->galleryImage2Name;
    }

    public function changeGalleryImage2Name(?string $galleryImage2Name): void
    {
        $this->galleryImage2Name = $galleryImage2Name;
    }

    public function galleryImage2Size(): ?int
    {
        return $this->galleryImage2Size;
    }

    public function changeGalleryImage2Size(?int $galleryImage2Size): void
    {
        $this->galleryImage2Size = $galleryImage2Size;
    }

    public function galleryImage3Name(): ?string
    {
        return $this->galleryImage3Name;
    }

    public function changeGalleryImage3Name(?string $galleryImage3Name): void
    {
        $this->galleryImage3Name = $galleryImage3Name;
    }

    public function galleryImage3Size(): ?int
    {
        return $this->galleryImage3Size;
    }

    public function changeGalleryImage3Size(?int $galleryImage3Size): void
    {
        $this->galleryImage3Size = $galleryImage3Size;
    }

    public function galleryImage4Name(): ?string
    {
        return $this->galleryImage4Name;
    }

    public function changeGalleryImage4Name(?string $galleryImage4Name): void
    {
        $this->galleryImage4Name = $galleryImage4Name;
    }

    public function galleryImage4Size(): ?int
    {
        return $this->galleryImage4Size;
    }

    public function changeGalleryImage4Size(?int $galleryImage4Size): void
    {
        $this->galleryImage4Size = $galleryImage4Size;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_values(array_filter([
            $this->imageName,
            $this->galleryImage2Name,
            $this->galleryImage3Name,
            $this->galleryImage4Name,
        ], static fn (?string $name): bool => null !== $name));
    }

    public function nameByPosition(int $position): ?string
    {
        return match ($position) {
            0 => $this->imageName,
            1 => $this->galleryImage2Name,
            2 => $this->galleryImage3Name,
            3 => $this->galleryImage4Name,
            default => null,
        };
    }

    /** @param 0|1|2|3 $position */
    public function remove(int $position): bool
    {
        return match ($position) {
            0 => $this->removePrimary(),
            1 => $this->removeSecondary($this->galleryImage2Name, $this->galleryImage2Size),
            2 => $this->removeSecondary($this->galleryImage3Name, $this->galleryImage3Size),
            3 => $this->removeSecondary($this->galleryImage4Name, $this->galleryImage4Size),
            default => throw new \InvalidArgumentException('Indice d\'image de galerie invalide.'),
        };
    }

    private function removePrimary(): bool
    {
        $changed = null !== $this->imageName || null !== $this->imageSize || null !== $this->imageAlt;
        $this->imageName = null;
        $this->imageSize = null;
        $this->imageAlt = null;

        return $changed;
    }

    private function removeSecondary(?string &$name, ?int &$size): bool
    {
        $changed = null !== $name || null !== $size;
        $name = null;
        $size = null;

        return $changed;
    }
}
