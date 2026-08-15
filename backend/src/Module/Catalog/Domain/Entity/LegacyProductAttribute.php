<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

final class LegacyProductAttribute
{
    public const STORAGE_CODE = 'storage';
    public const STORAGE_LABEL = 'Stockage';
    public const MEMORY_RAM_CODE = 'ram';
    public const MEMORY_RAM_LABEL = 'RAM';
    public const COLOR_CODE = 'color';
    public const COLOR_LABEL = 'Couleur';

    public static function label(string $code): string
    {
        return match (trim(mb_strtolower($code))) {
            self::STORAGE_CODE => self::STORAGE_LABEL,
            self::MEMORY_RAM_CODE => self::MEMORY_RAM_LABEL,
            self::COLOR_CODE => self::COLOR_LABEL,
            default => $code,
        };
    }

    /**
     * @return array{code:string,label:string,value:string}|null
     */
    public static function fromValue(string $code, ?string $value): ?array
    {
        $normalizedValue = null !== $value ? trim($value) : '';
        if ('' === $normalizedValue) {
            return null;
        }

        $normalizedCode = trim(mb_strtolower($code));
        if ('' === $normalizedCode) {
            return null;
        }

        return [
            'code' => $normalizedCode,
            'label' => self::label($normalizedCode),
            'value' => $normalizedValue,
        ];
    }
}
