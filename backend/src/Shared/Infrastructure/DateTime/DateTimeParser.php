<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DateTime;

final readonly class DateTimeParser
{
    public static function fromFormat(string $format, mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ('' === $normalized) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat($format, $normalized);
        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        $errors = \DateTimeImmutable::getLastErrors();
        if (false !== $errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $date;
    }

    public static function fromFormatOrThrow(string $format, mixed $value, string $message): \DateTimeImmutable
    {
        $date = self::fromFormat($format, $value);
        if (!$date instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException($message);
        }

        return $date;
    }
}
