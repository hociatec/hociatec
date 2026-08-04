<?php

declare(strict_types=1);

namespace App\Module\Quote\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'quote_services')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isFeaturedHome = false;

    #[Vich\UploadableField(mapping: 'service_images', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?object $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageAlt = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $imageExternalUrl = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationValue = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $durationUnit = null;

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    // VAT stored in basis points (e.g. 2000 => 20.00%)
    #[ORM\Column(type: 'integer')]
    private int $vatRateBps;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $title, int $priceCents, int $vatRateBps)
    {
        $this->title = $title;
        $this->priceCents = $priceCents;
        $this->vatRateBps = $vatRateBps;

        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

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
            $this->updatedAt = new \DateTimeImmutable();
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
        $this->imageName = $imageName;

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
        $this->imageAlt = $imageAlt;

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

    public function getDurationValue(): ?int
    {
        return $this->durationValue;
    }

    public function setDurationValue(?int $durationValue): self
    {
        $this->durationValue = $durationValue;

        return $this;
    }

    public function getDurationUnit(): ?string
    {
        return $this->durationUnit;
    }

    public function setDurationUnit(?string $durationUnit): self
    {
        $this->durationUnit = $durationUnit;

        return $this;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): self
    {
        $this->priceCents = $priceCents;

        return $this;
    }

    public function getVatRateBps(): int
    {
        return $this->vatRateBps;
    }

    public function setVatRateBps(int $vatRateBps): self
    {
        $this->vatRateBps = $vatRateBps;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
