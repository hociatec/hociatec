<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

trait ProductGalleryExtendedTrait
{
    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage2Name', size: 'galleryImage2Size')]
    private ?object $galleryImage2File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage2Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage2Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage3Name', size: 'galleryImage3Size')]
    private ?object $galleryImage3File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage3Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage3Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage4Name', size: 'galleryImage4Size')]
    private ?object $galleryImage4File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage4Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage4Size = null;

    public function getGalleryImage2File(): ?object
    {
        return $this->galleryImage2File;
    }

    public function getGalleryImage2Name(): ?string
    {
        return $this->gallery()->galleryImage2Name();
    }

    public function setGalleryImage2Name(?string $galleryImage2Name): self
    {
        $this->gallery()->changeGalleryImage2Name($galleryImage2Name);

        return $this;
    }

    public function getGalleryImage2Size(): ?int
    {
        return $this->gallery()->galleryImage2Size();
    }

    public function setGalleryImage2Size(?int $galleryImage2Size): self
    {
        $this->gallery()->changeGalleryImage2Size($galleryImage2Size);

        return $this;
    }

    public function getGalleryImage3File(): ?object
    {
        return $this->galleryImage3File;
    }

    public function getGalleryImage3Name(): ?string
    {
        return $this->gallery()->galleryImage3Name();
    }

    public function setGalleryImage3Name(?string $galleryImage3Name): self
    {
        $this->gallery()->changeGalleryImage3Name($galleryImage3Name);

        return $this;
    }

    public function getGalleryImage3Size(): ?int
    {
        return $this->gallery()->galleryImage3Size();
    }

    public function setGalleryImage3Size(?int $galleryImage3Size): self
    {
        $this->gallery()->changeGalleryImage3Size($galleryImage3Size);

        return $this;
    }

    public function getGalleryImage4File(): ?object
    {
        return $this->galleryImage4File;
    }

    public function getGalleryImage4Name(): ?string
    {
        return $this->gallery()->galleryImage4Name();
    }

    public function setGalleryImage4Name(?string $galleryImage4Name): self
    {
        $this->gallery()->changeGalleryImage4Name($galleryImage4Name);

        return $this;
    }

    public function getGalleryImage4Size(): ?int
    {
        return $this->gallery()->galleryImage4Size();
    }

    public function setGalleryImage4Size(?int $galleryImage4Size): self
    {
        $this->gallery()->changeGalleryImage4Size($galleryImage4Size);

        return $this;
    }

    /** @return list<string> */
    public function getGalleryImageNames(): array
    {
        return $this->gallery()->names();
    }

    public function getGalleryImageNameByPosition(int $position): ?string
    {
        return $this->gallery()->nameByPosition($position);
    }
}
