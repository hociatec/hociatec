<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

final class HeaderAccessSessionLocationResolver implements AccessSessionLocationResolver
{
    public function resolve(Request $request, ?string $clientIp): ?string
    {
        unset($clientIp);

        $city = $this->headerValue($request, [
            'CF-IPCity',
            'X-Vercel-IP-City',
            'X-Appengine-City',
            'CloudFront-Viewer-City',
            'X-Geo-City',
            'X-City',
        ]);
        $region = $this->headerValue($request, [
            'CF-Region',
            'X-Vercel-IP-Country-Region',
            'X-Appengine-Region',
            'CloudFront-Viewer-Country-Region-Name',
            'CloudFront-Viewer-Country-Region',
            'X-Geo-Region',
            'X-Region',
        ]);
        $country = $this->headerValue($request, [
            'CF-IPCountry',
            'X-Vercel-IP-Country',
            'X-Appengine-Country',
            'CloudFront-Viewer-Country-Name',
            'CloudFront-Viewer-Country',
            'X-Geo-Country',
            'X-Country',
            'X-Country-Code',
        ]);

        return $this->formatLocation($city, $region, $country);
    }

    /**
     * @param list<string> $headerNames
     */
    private function headerValue(Request $request, array $headerNames): ?string
    {
        foreach ($headerNames as $headerName) {
            $value = $this->normalizeValue($request->headers->get($headerName));
            if (null !== $value) {
                return $value;
            }
        }

        return null;
    }

    private function formatLocation(?string $city, ?string $region, ?string $country): ?string
    {
        $parts = [];
        foreach ([$city, $region, $country] as $part) {
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

    private function normalizeValue(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);
        if ('' === $normalized) {
            return null;
        }

        $lowered = mb_strtolower($normalized);
        if (in_array($lowered, ['unknown', 'unknown city', 'unknown region', 'xx', 'n/a', 'na'], true)) {
            return null;
        }

        return $normalized;
    }
}
