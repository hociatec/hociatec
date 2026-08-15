<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DateTime;

final readonly class DateTimeParser
{
    public static function fromFormat(string $format, mixed $value): ?\DateTimeImmutable
    {
        return \App\Shared\Domain\DateTime\DateTimeParser::fromFormat($format, $value);
    }

    public static function fromFormatOrThrow(string $format, mixed $value, string $message): \DateTimeImmutable
    {
        return \App\Shared\Domain\DateTime\DateTimeParser::fromFormatOrThrow($format, $value, $message);
    }

    public static function fromString(mixed $value): ?\DateTimeImmutable
    {
        return \App\Shared\Domain\DateTime\DateTimeParser::fromString($value);
    }

    public static function fromStringOrThrow(mixed $value, string $message): \DateTimeImmutable
    {
        return \App\Shared\Domain\DateTime\DateTimeParser::fromStringOrThrow($value, $message);
    }
}
