<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use App\Module\Catalog\Domain\ValueObject\ProductColor;
use App\Module\Catalog\Domain\ValueObject\ProductMemoryRam;
use App\Module\Catalog\Domain\ValueObject\ProductStorageCapacity;
use App\Module\Catalog\Domain\ValueObject\ProductVariantGroup;
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

    public function changeVariantGroup(ProductVariantGroup|string|null $variantGroup): void
    {
        $this->variantGroup = $variantGroup instanceof ProductVariantGroup
            ? $variantGroup->value()
            : ProductVariantGroup::fromNullable($variantGroup)->value();
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

    public function changeStorageCapacity(ProductStorageCapacity|string|null $capacity): void
    {
        $this->storageCapacity = $capacity instanceof ProductStorageCapacity
            ? $capacity->value()
            : ProductStorageCapacity::fromNullable($capacity)->value();
    }

    public function memoryRam(): ?string
    {
        return $this->memoryRam;
    }

    public function changeMemoryRam(ProductMemoryRam|string|null $memoryRam): void
    {
        $this->memoryRam = $memoryRam instanceof ProductMemoryRam
            ? $memoryRam->value()
            : ProductMemoryRam::fromNullable($memoryRam)->value();
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function changeColor(ProductColor|string|null $color): void
    {
        $this->color = $color instanceof ProductColor
            ? $color->value()
            : ProductColor::fromNullable($color)->value();
    }
}
