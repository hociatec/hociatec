<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

final class OutboundUrlGuard
{
    /** @var array<string, true> */
    private const FORBIDDEN_HOSTS = [
        'localhost' => true,
        'localhost.localdomain' => true,
        'metadata.google.internal' => true,
        'metadata' => true,
    ];

    public static function assertAllowedHttpUrl(string $url): void
    {
        $parts = parse_url(trim($url));
        $scheme = strtolower(is_string($parts['scheme'] ?? null) ? $parts['scheme'] : '');
        $host = strtolower(is_string($parts['host'] ?? null) ? $parts['host'] : '');

        if (!in_array($scheme, ['http', 'https'], true) || '' === $host) {
            throw new \InvalidArgumentException('URL sortante non autorisée.');
        }

        if (isset(self::FORBIDDEN_HOSTS[$host]) || str_ends_with($host, '.internal')) {
            throw new \InvalidArgumentException('URL sortante non autorisée.');
        }

        if (self::isForbiddenIpLiteral($host)) {
            throw new \InvalidArgumentException('URL sortante non autorisée.');
        }
    }

    public static function assertAllowedHttpsRedirectBase(string $url): void
    {
        $parts = parse_url(trim($url));
        $scheme = strtolower(is_string($parts['scheme'] ?? null) ? $parts['scheme'] : '');
        $host = strtolower(is_string($parts['host'] ?? null) ? $parts['host'] : '');

        if ('https' !== $scheme || '' === $host || self::isForbiddenIpLiteral($host) || isset(self::FORBIDDEN_HOSTS[$host])) {
            throw new \InvalidArgumentException('URL de redirection non autorisée.');
        }
    }

    private static function isForbiddenIpLiteral(string $host): bool
    {
        if (false === filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (str_starts_with($host, '127.') || '::1' === $host) {
            return true;
        }

        if (str_starts_with($host, '10.')
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '169.254.')
        ) {
            return true;
        }

        if (preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host)) {
            return true;
        }

        return false;
    }
}
