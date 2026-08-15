<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Http;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Symfony\Component\HttpFoundation\Request;

final class GeoIpAccessSessionLocationResolver implements AccessSessionLocationResolver
{
    private ?Reader $reader = null;
    private bool $readerInitialized = false;

    public function __construct(
        private readonly string $databasePath = '',
        private readonly string $locales = 'fr,en',
    ) {
    }

    public function resolve(Request $request, ?string $clientIp): ?string
    {
        unset($request);

        if (null === $clientIp || !$this->isLookupEligible($clientIp)) {
            return null;
        }

        $reader = $this->reader();
        if (null === $reader) {
            return null;
        }

        try {
            $city = $reader->city($clientIp);
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }

        $cityName = $this->normalizeValue($city->city->name);
        $regionName = $this->normalizeValue($city->mostSpecificSubdivision->name);
        $countryName = $this->normalizeValue($city->country->name);

        $parts = [];
        foreach ([$cityName, $regionName, $countryName] as $part) {
            if (null === $part) {
                continue;
            }

            if (!in_array($part, $parts, true)) {
                $parts[] = $part;
            }
        }

        if ([] === $parts) {
            return null;
        }

        return implode(', ', $parts);
    }

    private function reader(): ?Reader
    {
        if ($this->readerInitialized) {
            return $this->reader;
        }

        $this->readerInitialized = true;

        $path = trim($this->databasePath);
        if ('' === $path || !is_file($path) || !is_readable($path)) {
            return null;
        }

        try {
            $this->reader = new Reader($path, $this->resolveLocales());
        } catch (InvalidDatabaseException) {
            return null;
        }

        return $this->reader;
    }

    /**
     * @return list<string>
     */
    private function resolveLocales(): array
    {
        $parts = array_values(array_filter(array_map(
            static fn (string $locale): string => trim($locale),
            explode(',', $this->locales)
        ), static fn (string $locale): bool => '' !== $locale));

        return [] !== $parts ? $parts : ['fr', 'en'];
    }

    private function isLookupEligible(?string $clientIp): bool
    {
        if (null === $clientIp) {
            return false;
        }

        return false !== filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function normalizeValue(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);

        return '' === $normalized ? null : $normalized;
    }
}
