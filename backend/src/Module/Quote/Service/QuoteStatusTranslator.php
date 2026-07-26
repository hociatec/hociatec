<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;

/**
 * Provides a single place for translating quote status codes.
 */
final class QuoteStatusTranslator
{
    /**
     * @var array<string, string>
     */
    private const LABELS = [
        Quote::STATUS_DRAFT => 'brouillon',
        Quote::STATUS_SENT => 'envoyé',
        Quote::STATUS_ACCEPTED => 'accepté',
        Quote::STATUS_REFUSED => 'refusé',
        Quote::STATUS_EXPIRED => 'expiré',
    ];

    private function __construct()
    {
    }

    public static function toLabel(string $status): string
    {
        $normalized = self::normalize($status);

        return self::LABELS[$normalized] ?? $status;
    }

    public static function toCode(string $value): string
    {
        $normalized = self::normalize($value);

        foreach (self::LABELS as $code => $label) {
            if ($normalized === $code || $normalized === $label) {
                return $code;
            }
        }

        return $normalized;
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        $options = [];
        foreach (self::LABELS as $value => $label) {
            $options[] = ['value' => $value, 'label' => ucfirst($label)];
        }

        return $options;
    }

    private static function normalize(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            return $value;
        }

        $value = self::toLower($value);

        $value = strtr($value, [
            "\xC3\xA9" => 'e',
            "\xC3\xA8" => 'e',
            "\xC3\xAA" => 'e',
            "\xC3\xA0" => 'a',
            "\xC3\xB9" => 'u',
            "\xC3\xBB" => 'u',
            "\xC3\xB4" => 'o',
            "\xC3\xAF" => 'i',
        ]);

        return $value;
    }

    private static function toLower(string $value): string
    {
        if (\function_exists('mb_strtolower')) {
            return mb_strtolower($value);
        }

        return strtolower($value);
    }
}
