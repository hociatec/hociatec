<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Mapper;

use App\Shared\Domain\DateTime\DateTimeParser;

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

        return DateTimeParser::fromFormatOrThrow(
            'Y-m-d',
            $normalized,
            'Format de date invalide. Utilisez YYYY-MM-DD.'
        )->setTime(0, 0);
    }
}
