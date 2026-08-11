<?php

declare(strict_types=1);

namespace App\Shared\Application\Mail;

final class EmailHeaderSanitizer
{
    public static function subject(string $value): string
    {
        return self::singleLine($value, 255);
    }

    public static function displayName(string $value): string
    {
        return self::singleLine($value, 180);
    }

    private static function singleLine(string $value, int $maxLength): string
    {
        $value = str_replace(["\r", "\n"], ' ', trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return mb_substr($value, 0, $maxLength);
    }
}
