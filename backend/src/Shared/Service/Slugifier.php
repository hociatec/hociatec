<?php

declare(strict_types=1);

namespace App\Shared\Service;

trait Slugifier
{
    private function slugifyValue(string $value, string $fallback): string
    {
        $value = $this->repairMojibake(trim($value));
        $value = $this->transliterate($value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;
        $value = trim($value, '-');

        return '' !== $value ? $value : $fallback;
    }

    private function transliterate(string $value): string
    {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC');

        if (null !== $transliterator) {
            return $transliterator->transliterate($value) ?: $value;
        }

        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    }

    private function repairMojibake(string $value): string
    {
        if (!preg_match('/(?:Ã.|Â.)/u', $value)) {
            return $value;
        }

        $repaired = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');

        return '' !== $repaired ? $repaired : $value;
    }
}
