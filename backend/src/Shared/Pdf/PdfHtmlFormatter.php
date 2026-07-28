<?php

declare(strict_types=1);

namespace App\Shared\Pdf;

final readonly class PdfHtmlFormatter
{
    public function money(int $amountCents): string
    {
        return number_format($amountCents / 100, 2, ',', ' ').' EUR';
    }

    public function date(?string $value, bool $fallbackToRawValue = false): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date instanceof \DateTimeImmutable) {
            return $date->format('d/m/Y');
        }

        return $fallbackToRawValue ? $value : '-';
    }

    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function paragraphsFromLines(string $value, bool $emptyDash = false): string
    {
        $parts = preg_split('/\R+/', trim($value)) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => '' !== $part));

        if ([] === $parts) {
            return $emptyDash ? '<p>-</p>' : '';
        }

        return implode('', array_map(fn (string $part): string => '<p>'.$this->escape($part).'</p>', $parts));
    }
}
