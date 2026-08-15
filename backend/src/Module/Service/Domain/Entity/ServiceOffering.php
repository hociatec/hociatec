<?php

declare(strict_types=1);

namespace App\Module\Service\Domain\Entity;

use App\Module\Service\Domain\Entity\Concern\ServiceOfferingBillingConcern;
use App\Module\Service\Domain\Entity\Concern\ServiceOfferingMediaConcern;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'quote_services')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class ServiceOffering
{
    use ServiceOfferingBillingConcern;
    use ServiceOfferingMediaConcern;

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

    #[ORM\Column(type: 'integer')]
    private int $vatRateBps;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $title, int $priceCents, int $vatRateBps)
    {
        $this->setTitle($title);
        $this->setPriceCents($priceCents);
        $this->setVatRateBps($vatRateBps);
        $this->initializeTimestamps();
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
        $this->description = $this->normalizeOptionalText($description);

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $this->normalizeOptionalText($unit);

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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->initializeTimestamps();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->touch();
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);

        return '' === $normalized ? null : $normalized;
    }

    private function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
