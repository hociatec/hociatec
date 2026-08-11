<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

final class LikeSearchHelper
{
    private const MAX_TERM_LENGTH = 120;

    public static function normalized(?string $search): string
    {
        return mb_substr(trim((string) $search), 0, self::MAX_TERM_LENGTH);
    }

    public static function lowered(?string $search): string
    {
        return mb_strtolower(self::normalized($search));
    }

    public static function containsPattern(?string $search, bool $lowered = false): ?string
    {
        $normalized = $lowered ? self::lowered($search) : self::normalized($search);

        return '' === $normalized ? null : '%'.$normalized.'%';
    }

    public static function prefixPattern(?string $search, bool $lowered = false): ?string
    {
        $normalized = $lowered ? self::lowered($search) : self::normalized($search);

        return '' === $normalized ? null : $normalized.'%';
    }
}
