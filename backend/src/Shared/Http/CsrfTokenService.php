<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CsrfTokenService
{
    public const COOKIE_NAME = 'hociatec_csrf';
    public const HEADER_NAME = 'X-CSRF-Token';

    private const TOKEN_BYTES = 32;
    private const TOKEN_TTL_SECONDS = 7200;

    public function __construct(
        private readonly string $environment,
    ) {
    }

    public function issue(Response $response, Request $request): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        $response->headers->setCookie($this->createCookie($request, $token));

        return $token;
    }

    public function isValid(Request $request): bool
    {
        $cookieToken = (string) $request->cookies->get(self::COOKIE_NAME, '');
        $headerToken = (string) $request->headers->get(self::HEADER_NAME, '');

        return $cookieToken !== ''
            && $headerToken !== ''
            && hash_equals($cookieToken, $headerToken);
    }

    private function createCookie(Request $request, string $token): Cookie
    {
        return Cookie::create(
            self::COOKIE_NAME,
            $token,
            time() + self::TOKEN_TTL_SECONDS,
            '/api',
            null,
            $request->isSecure() || $this->environment === 'prod',
            false,
            false,
            Cookie::SAMESITE_LAX,
        );
    }
}
