<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Exception\PublicInvalidArgumentException;
use App\Shared\Domain\ValueObject\DecimalNumber;
use App\Shared\Infrastructure\DateTime\DateTimeParser;

final class RequestPayloadMapper
{
    private function __construct()
    {
    }

    /** @param array<string, mixed> $payload */
    public static function string(array $payload, string $name, string $default = ''): string
    {
        $value = $payload[$name] ?? $default;

        return is_string($value) || is_numeric($value) ? trim((string) $value) : $default;
    }

    /** @param array<string, mixed> $payload */
    public static function nullableString(array $payload, string $name): ?string
    {
        $value = self::string($payload, $name);

        return '' !== $value ? $value : null;
    }

    /** @param array<string, mixed> $payload */
    public static function int(array $payload, string $name, int $default = 0): int
    {
        $value = $payload[$name] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    /** @param array<string, mixed> $payload */
    public static function bool(array $payload, string $name, bool $default = false): bool
    {
        $value = $payload[$name] ?? $default;

        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function priceCents(mixed $price): int
    {
        $cents = DecimalNumber::toScaledInt($price, 2);
        if (null !== $cents) {
            return $cents;
        }

        if ('' === trim((string) $price)) {
            return 0;
        }

        throw new PublicInvalidArgumentException('Le prix doit etre positif.', 422, 'UNPROCESSABLE_ENTITY');
    }

    public static function dateOrNull(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return DateTimeParser::fromString($value);
    }

    public static function normalizedCode(mixed $value): string
    {
        return is_string($value) ? mb_strtoupper(trim($value)) : '';
    }

    public static function generatedCode(string $seed): string
    {
        $base = '' !== trim($seed) ? $seed : 'CLIENT';
        $base = preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base) ?: 'CLIENT';
        $base = trim(strtoupper($base), '-');

        return substr($base, 0, 12).'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
