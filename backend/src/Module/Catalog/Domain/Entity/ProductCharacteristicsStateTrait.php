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
        return $this->characteristics->attributeValue(LegacyProductAttribute::STORAGE_CODE);
    }

    public function setStorageCapacity(?string $storageCapacity): self
    {
        $this->characteristics->changeAttributeValue(
            LegacyProductAttribute::STORAGE_CODE,
            LegacyProductAttribute::STORAGE_LABEL,
            $storageCapacity,
        );

        return $this;
    }

    public function getMemoryRam(): ?string
    {
        return $this->characteristics->attributeValue(LegacyProductAttribute::MEMORY_RAM_CODE);
    }

    public function setMemoryRam(?string $memoryRam): self
    {
        $this->characteristics->changeAttributeValue(
            LegacyProductAttribute::MEMORY_RAM_CODE,
            LegacyProductAttribute::MEMORY_RAM_LABEL,
            $memoryRam,
        );

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->characteristics->attributeValue(LegacyProductAttribute::COLOR_CODE);
    }

    public function setColor(?string $color): self
    {
        $this->characteristics->changeAttributeValue(
            LegacyProductAttribute::COLOR_CODE,
            LegacyProductAttribute::COLOR_LABEL,
            $color,
        );

        return $this;
    }

    /**
     * @return list<array{code:string,label:string,value:string}>
     */
    public function getAttributes(): array
    {
        return $this->characteristics->attributes();
    }

    /**
     * @param list<array<string, mixed>> $attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->characteristics->replaceAttributes($attributes);

        return $this;
    }
}
