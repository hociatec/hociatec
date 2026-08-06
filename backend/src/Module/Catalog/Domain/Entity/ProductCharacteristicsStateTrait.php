<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

trait ProductCharacteristicsStateTrait
{
    public function getVariantGroup(): ?string
    {
        return $this->characteristics->variantGroup();
    }

    public function setVariantGroup(?string $variantGroup): self
    {
        $this->characteristics->changeVariantGroup($variantGroup);

        return $this;
    }

    public function getVariantPosition(): int
    {
        return $this->characteristics->variantPosition();
    }

    public function setVariantPosition(int $variantPosition): self
    {
        $this->characteristics->changeVariantPosition($variantPosition);

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->characteristics->releaseYear();
    }

    public function setReleaseYear(?int $releaseYear): self
    {
        $this->characteristics->changeReleaseYear($releaseYear);

        return $this;
    }

    public function getStorageCapacity(): ?string
    {
        return $this->characteristics->storageCapacity();
    }

    public function setStorageCapacity(?string $storageCapacity): self
    {
        $this->characteristics->changeStorageCapacity($storageCapacity);

        return $this;
    }

    public function getMemoryRam(): ?string
    {
        return $this->characteristics->memoryRam();
    }

    public function setMemoryRam(?string $memoryRam): self
    {
        $this->characteristics->changeMemoryRam($memoryRam);

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->characteristics->color();
    }

    public function setColor(?string $color): self
    {
        $this->characteristics->changeColor($color);

        return $this;
    }
}
