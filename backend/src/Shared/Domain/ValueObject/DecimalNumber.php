<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class DecimalNumber
{
    private function __construct()
    {
    }

    public static function toScaledInt(mixed $value, int $scale): ?int
    {
        if ($scale < 0) {
            throw new \InvalidArgumentException('Decimal scale must be positive.');
        }

        $normalized = self::normalize($value);
        if (null === $normalized) {
            return null;
        }

        $negative = str_starts_with($normalized, '-');
        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $whole = '' !== $whole ? $whole : '0';
        $fraction = preg_replace('/\D/', '', $fraction) ?? '';

        $fraction = str_pad($fraction, $scale + 1, '0');
        $kept = 0 === $scale ? '' : substr($fraction, 0, $scale);
        $roundDigit = (int) $fraction[$scale];

        $scaled = (int) $whole;
        if ($scale > 0) {
            $scaled = ($scaled * (10 ** $scale)) + (int) $kept;
        }

        if ($roundDigit >= 5) {
            ++$scaled;
        }

        return $negative ? -$scaled : $scaled;
    }

    private static function normalize(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            $value = rtrim(rtrim(sprintf('%.14F', $value), '0'), '.');
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));
        if ('' === $normalized) {
            return null;
        }

        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
