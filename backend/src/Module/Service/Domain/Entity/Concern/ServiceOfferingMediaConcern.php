<?php

declare(strict_types=1);

namespace App\Module\Service\Domain\Entity\Concern;

trait ServiceOfferingMediaConcern
{
    public function isFeaturedHome(): bool
    {
        return $this->isFeaturedHome;
    }

    public function setIsFeaturedHome(bool $isFeaturedHome): self
    {
        $this->isFeaturedHome = $isFeaturedHome;

        return $this;
    }

    public function setImageFile(?object $imageFile): self
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->touch();
        }

        return $this;
    }

    public function getImageFile(): ?object
    {
        return $this->imageFile;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $this->normalizeOptionalText($imageName);

        return $this;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function setImageSize(?int $imageSize): self
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    public function getImageAlt(): ?string
    {
        return $this->imageAlt;
    }

    public function setImageAlt(?string $imageAlt): self
    {
        $this->imageAlt = $this->normalizeOptionalText($imageAlt);

        return $this;
    }

    public function getImageExternalUrl(): ?string
    {
        return $this->imageExternalUrl;
    }

    public function setImageExternalUrl(?string $imageExternalUrl): self
    {
        $this->imageExternalUrl = $this->normalizeOptionalText($imageExternalUrl);

        return $this;
    }
}
