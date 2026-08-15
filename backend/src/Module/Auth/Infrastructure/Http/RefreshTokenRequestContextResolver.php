<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Http;

use App\Module\Auth\Application\DTO\RefreshTokenContext;
use App\Shared\Infrastructure\Http\AuthCookieResponseWriter;
use Symfony\Component\HttpFoundation\Request;

final readonly class RefreshTokenRequestContextResolver
{
    public function resolve(Request $request): RefreshTokenContext
    {
        $userAgent = $this->trimOrNull($request->headers->get('User-Agent'));

        $platform = $this->trimOrNull($request->headers->get('X-Hociatec-Client-Platform')) ?? $this->detectPlatform($userAgent);
        $client = $this->trimOrNull($request->headers->get('X-Hociatec-Client-App')) ?? $this->detectClient($userAgent);
        $device = $this->trimOrNull($request->headers->get('X-Hociatec-Device-Name')) ?? $this->detectDevice($userAgent, $platform, $client);

        return new RefreshTokenContext(
            $device,
            $platform,
            $client,
            $this->resolveLocation($request),
            $userAgent,
            $this->trimOrNull($request->getClientIp()),
        );
    }

    public function currentRefreshTokenSelector(Request $request): ?string
    {
        $cookie = $this->trimOrNull($request->cookies->get(AuthCookieResponseWriter::REFRESH_COOKIE));
        if (null === $cookie) {
            return null;
        }

        $parts = explode('.', $cookie, 2);

        return 2 === count($parts) && '' !== $parts[0] ? $parts[0] : null;
    }

    private function detectPlatform(?string $userAgent): ?string
    {
        $ua = mb_strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'iphone'), str_contains($ua, 'ipad'), str_contains($ua, 'ios') => 'iOS',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'mac os x'), str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'linux') => 'Linux',
            default => null,
        };
    }

    private function detectClient(?string $userAgent): ?string
    {
        $ua = mb_strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'edg/') => 'Microsoft Edge',
            str_contains($ua, 'opr/'), str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'chrome/') && !str_contains($ua, 'edg/') => 'Chrome',
            str_contains($ua, 'safari/') && !str_contains($ua, 'chrome/') => 'Safari',
            default => null,
        };
    }

    private function detectDevice(?string $userAgent, ?string $platform, ?string $client): ?string
    {
        $ua = mb_strtolower((string) $userAgent);

        if ('Application iPhone' === $client && null !== $platform) {
            return 'Appareil '.trim($platform);
        }

        return match (true) {
            str_contains($ua, 'iphone') => 'iPhone',
            str_contains($ua, 'ipad') => 'iPad',
            str_contains($ua, 'android') && str_contains($ua, 'mobile') => 'Téléphone Android',
            str_contains($ua, 'android') => 'Tablette Android',
            str_contains($ua, 'macintosh') => 'Mac',
            str_contains($ua, 'windows') => 'PC Windows',
            str_contains($ua, 'linux') => 'PC Linux',
            default => null,
        };
    }

    private function resolveLocation(Request $request): ?string
    {
        $city = $this->trimOrNull(
            $request->headers->get('CF-IPCity')
            ?? $request->headers->get('X-Vercel-IP-City')
            ?? $request->headers->get('X-Appengine-City')
        );
        $region = $this->trimOrNull(
            $request->headers->get('CF-Region')
            ?? $request->headers->get('X-Vercel-IP-Country-Region')
            ?? $request->headers->get('X-Appengine-Region')
        );

        if (null !== $city && null !== $region) {
            return $city.', '.$region;
        }

        return $city ?? $region;
    }

    private function trimOrNull(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);

        return '' === $normalized ? null : $normalized;
    }
}
