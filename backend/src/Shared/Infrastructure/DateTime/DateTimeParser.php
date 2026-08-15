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
}
