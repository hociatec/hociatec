<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

trait ProductGalleryInfoTrait
{
    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?object $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageAlt = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $imageExternalUrl = null;

    public function getImageFile(): ?object
    {
        return $this->imageFile;
    }

    public function getImageName(): ?string
    {
        return $this->gallery()->imageName();
    }

    public function setImageName(?string $imageName): self
    {
        $this->gallery()->changeImageName($imageName);

        return $this;
    }

    public function getImageSize(): ?int
    {
        return $this->gallery()->imageSize();
    }

    public function setImageSize(?int $imageSize): self
    {
        $this->gallery()->changeImageSize($imageSize);

        return $this;
    }

    public function getImageAlt(): ?string
    {
        return $this->gallery()->imageAlt();
    }

    public function setImageAlt(?string $imageAlt): self
    {
        $this->gallery()->changeImageAlt($imageAlt);

        return $this;
    }

    public function getImageExternalUrl(): ?string
    {
        return $this->imageExternalUrl;
    }

    public function setImageExternalUrl(?string $imageExternalUrl): self
    {
        $this->imageExternalUrl = $imageExternalUrl;

        return $this;
    }
}
