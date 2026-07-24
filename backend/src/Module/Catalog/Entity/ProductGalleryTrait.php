<?php

declare(strict_types=1);

namespace App\Module\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

trait ProductGalleryTrait
{
    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage2Name', size: 'galleryImage2Size')]
    private ?File $galleryImage2File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage2Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage2Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage3Name', size: 'galleryImage3Size')]
    private ?File $galleryImage3File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage3Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage3Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage4Name', size: 'galleryImage4Size')]
    private ?File $galleryImage4File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage4Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage4Size = null;

    public function getGalleryImage2File(): ?File
    {
        return $this->galleryImage2File;
    }

    public function getGalleryImage2Name(): ?string
    {
        return $this->galleryImage2Name;
    }

    public function setGalleryImage2Name(?string $galleryImage2Name): self
    {
        $this->galleryImage2Name = $galleryImage2Name;

        return $this;
    }

    public function getGalleryImage2Size(): ?int
    {
        return $this->galleryImage2Size;
    }

    public function setGalleryImage2Size(?int $galleryImage2Size): self
    {
        $this->galleryImage2Size = $galleryImage2Size;

        return $this;
    }

    public function getGalleryImage3File(): ?File
    {
        return $this->galleryImage3File;
    }

    public function getGalleryImage3Name(): ?string
    {
        return $this->galleryImage3Name;
    }

    public function setGalleryImage3Name(?string $galleryImage3Name): self
    {
        $this->galleryImage3Name = $galleryImage3Name;

        return $this;
    }

    public function getGalleryImage3Size(): ?int
    {
        return $this->galleryImage3Size;
    }

    public function setGalleryImage3Size(?int $galleryImage3Size): self
    {
        $this->galleryImage3Size = $galleryImage3Size;

        return $this;
    }

    public function getGalleryImage4File(): ?File
    {
        return $this->galleryImage4File;
    }

    public function getGalleryImage4Name(): ?string
    {
        return $this->galleryImage4Name;
    }

    public function setGalleryImage4Name(?string $galleryImage4Name): self
    {
        $this->galleryImage4Name = $galleryImage4Name;

        return $this;
    }

    public function getGalleryImage4Size(): ?int
    {
        return $this->galleryImage4Size;
    }

    public function setGalleryImage4Size(?int $galleryImage4Size): self
    {
        $this->galleryImage4Size = $galleryImage4Size;

        return $this;
    }

    /**
     * @param 0|1|2|3 $position
     */
    public function setGalleryImageFile(int $position, ?File $file): self
    {
        if (0 === $position) {
            return $this->setImageFile($file);
        }

        switch ($position) {
            case 1:
                $this->galleryImage2File = $file;
                if (null !== $file || null !== $this->galleryImage2Name) {
                    $this->updatedAt = new \DateTimeImmutable();
                }
                break;
            case 2:
                $this->galleryImage3File = $file;
                if (null !== $file || null !== $this->galleryImage3Name) {
                    $this->updatedAt = new \DateTimeImmutable();
                }
                break;
            case 3:
                $this->galleryImage4File = $file;
                if (null !== $file || null !== $this->galleryImage4Name) {
                    $this->updatedAt = new \DateTimeImmutable();
                }
                break;
            default:
                throw new \InvalidArgumentException('Indice d\'image de galerie invalide.');
        }

        return $this;
    }

    /**
     * @param 0|1|2|3 $position
     */
    public function removeGalleryImage(int $position): self
    {
        switch ($position) {
            case 0:
                $this
                    ->setImageFile(null)
                    ->setImageName(null)
                    ->setImageSize(null)
                    ->setImageAlt(null);
                break;
            case 1:
                if (null !== $this->galleryImage2Name) {
                    $this->galleryImage2Name = null;
                    $this->galleryImage2Size = null;
                    $this->updatedAt = new \DateTimeImmutable();
                }
                $this->galleryImage2File = null;
                break;
            case 2:
                if (null !== $this->galleryImage3Name) {
                    $this->galleryImage3Name = null;
                    $this->galleryImage3Size = null;
                    $this->updatedAt = new \DateTimeImmutable();
                }
                $this->galleryImage3File = null;
                break;
            case 3:
                if (null !== $this->galleryImage4Name) {
                    $this->galleryImage4Name = null;
                    $this->galleryImage4Size = null;
                    $this->updatedAt = new \DateTimeImmutable();
                }
                $this->galleryImage4File = null;
                break;
            default:
                throw new \InvalidArgumentException('Indice d\'image de galerie invalide.');
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getGalleryImageNames(): array
    {
        $names = [];

        foreach ([0, 1, 2, 3] as $position) {
            $name = $this->getGalleryImageNameByPosition($position);
            if (null !== $name) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function getGalleryImageNameByPosition(int $position): ?string
    {
        return match ($position) {
            0 => $this->imageName,
            1 => $this->galleryImage2Name,
            2 => $this->galleryImage3Name,
            3 => $this->galleryImage4Name,
            default => null,
        };
    }
}
