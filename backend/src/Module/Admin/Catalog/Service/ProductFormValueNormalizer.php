<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Service;

final class ProductFormValueNormalizer
{
    private function __construct()
    {
    }

    public static function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }
        if (is_int($value)) {
            return 1 === $value;
        }

        return (bool) $value;
    }

    public static function optionalInt(mixed $value): ?int
    {
        return null !== $value && '' !== $value && is_numeric($value) ? (int) $value : null;
    }

    public static function optionalString(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return '' !== $normalized ? $normalized : null;
    }

    public static function priceToCents(mixed $value): int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }
        if (is_string($value)) {
            $normalized = str_replace(',', '.', trim($value));
            if (is_numeric($normalized)) {
                return (int) round((float) $normalized * 100);
            }
        }

        return -1;
    }

    public static function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Date de remise invalide.', previous: $exception);
        }
    }
}
