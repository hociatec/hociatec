<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

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

    /** @var list<array{code:string,label:string,value:string}> */
    #[ORM\Column(type: 'json')]
    private array $attributes = [];

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

    /**
     * @return list<array{code:string,label:string,value:string}>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param list<array<string, mixed>> $attributes
     */
    public function replaceAttributes(array $attributes): void
    {
        $normalized = [];

        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $code = $this->normalizeAttributeCode($attribute['code'] ?? null);
            $label = $this->normalizeAttributeText($attribute['label'] ?? null);
            $value = $this->normalizeAttributeText($attribute['value'] ?? null);

            if (null === $code || null === $label || null === $value) {
                continue;
            }

            $normalized[$code] = [
                'code' => $code,
                'label' => $label,
                'value' => $value,
            ];
        }

        $this->attributes = array_values($normalized);
    }

    public function attributeValue(string $code): ?string
    {
        $normalizedCode = $this->normalizeAttributeCode($code);
        if (null === $normalizedCode) {
            return null;
        }

        foreach ($this->attributes as $attribute) {
            if (($attribute['code'] ?? null) === $normalizedCode) {
                return $attribute['value'] ?? null;
            }
        }

        return null;
    }

    public function changeAttributeValue(string $code, ?string $label, ?string $value): void
    {
        $normalizedCode = $this->normalizeAttributeCode($code);
        if (null === $normalizedCode) {
            return;
        }

        $normalizedLabel = $this->normalizeAttributeText($label) ?? ucfirst(str_replace('-', ' ', $normalizedCode));
        $normalizedValue = $this->normalizeAttributeText($value);

        $attributes = $this->attributes;
        $updated = false;

        foreach ($attributes as $index => $attribute) {
            if (($attribute['code'] ?? null) !== $normalizedCode) {
                continue;
            }

            $updated = true;

            if (null === $normalizedValue) {
                unset($attributes[$index]);
            } else {
                $attributes[$index] = [
                    'code' => $normalizedCode,
                    'label' => $normalizedLabel,
                    'value' => $normalizedValue,
                ];
            }

            break;
        }

        if (!$updated && null !== $normalizedValue) {
            $attributes[] = [
                'code' => $normalizedCode,
                'label' => $normalizedLabel,
                'value' => $normalizedValue,
            ];
        }

        $this->attributes = array_values($attributes);
    }

    private function normalizeAttributeCode(mixed $code): ?string
    {
        if (!is_string($code)) {
            return null;
        }

        $normalized = trim(mb_strtolower($code));
        $normalized = preg_replace('/[^a-z0-9]+/u', '-', $normalized) ?? $normalized;
        $normalized = trim($normalized, '-');

        return '' !== $normalized ? $normalized : null;
    }

    private function normalizeAttributeText(mixed $value): ?string
    {
        if (!is_scalar($value) && null !== $value) {
            return null;
        }

        $normalized = trim((string) $value);

        return '' !== $normalized ? $normalized : null;
    }
}
