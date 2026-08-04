<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Mapper;

final class QuoteValueNormalizer
{
    private function __construct()
    {
    }

    public static function strOrNull(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim((string) $value);

        return '' === $normalized ? null : $normalized;
    }

    public static function dateOrNull(mixed $value): ?\DateTimeImmutable
    {
        $normalized = self::strOrNull($value);
        if (null === $normalized) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $normalized);
        if (false === $date) {
            throw new \InvalidArgumentException('Format de date invalide. Utilisez YYYY-MM-DD.');
        }

        return $date->setTime(0, 0);
    }
}
