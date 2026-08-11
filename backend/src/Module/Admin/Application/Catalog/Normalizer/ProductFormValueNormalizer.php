<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Normalizer;

use App\Shared\Domain\ValueObject\DecimalNumber;

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
        $cents = DecimalNumber::toScaledInt($value, 2);
        if (null !== $cents) {
            return $cents;
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
        } catch (\DateMalformedStringException $exception) {
            throw new \InvalidArgumentException('Date de remise invalide.', previous: $exception);
        }
    }
}
