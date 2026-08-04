<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProductCharacteristics
{
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $variantGroup = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 1])]
    private int $variantPosition = 1;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $releaseYear = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $storageCapacity = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $memoryRam = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $color = null;

    public function variantGroup(): ?string
    {
        return $this->variantGroup;
    }

    public function changeVariantGroup(?string $variantGroup): void
    {
        $normalized = null !== $variantGroup ? trim($variantGroup) : null;
        $this->variantGroup = '' !== $normalized ? $normalized : null;
    }

    public function variantPosition(): int
    {
        return $this->variantPosition;
    }

    public function changeVariantPosition(int $position): void
    {
        if ($position < 1) {
            throw new \InvalidArgumentException('Position de variante invalide.');
        }

        $this->variantPosition = $position;
    }

    public function releaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function changeReleaseYear(?int $year): void
    {
        if (null !== $year && ($year < 2000 || $year > 2100)) {
            throw new \InvalidArgumentException('Année de modèle invalide.');
        }

        $this->releaseYear = $year;
    }

    public function storageCapacity(): ?string
    {
        return $this->storageCapacity;
    }

    public function changeStorageCapacity(?string $capacity): void
    {
        $normalized = null !== $capacity ? trim($capacity) : null;
        $this->storageCapacity = '' !== $normalized ? $normalized : null;
    }

    public function memoryRam(): ?string
    {
        return $this->memoryRam;
    }

    public function changeMemoryRam(?string $memoryRam): void
    {
        $normalized = null !== $memoryRam ? trim($memoryRam) : null;
        $this->memoryRam = '' !== $normalized ? $normalized : null;
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function changeColor(?string $color): void
    {
        $normalized = null !== $color ? trim($color) : null;
        $this->color = '' !== $normalized ? $normalized : null;
    }
}
