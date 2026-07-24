<?php

declare(strict_types=1);

namespace App\Module\Auth\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthCookieService
{
    public const ACCESS_COOKIE = 'hociatec_access';
    public const REFRESH_COOKIE = 'hociatec_refresh';

    private const ACCESS_TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly string $environment,
    ) {
    }

    public function attachLoginCookies(
        Response $response,
        Request $request,
        string $jwt,
        string $refreshToken,
        string $refreshTokenExpiresAt,
    ): void {
        $response->headers->setCookie($this->createAccessCookie($request, $jwt));
        $response->headers->setCookie($this->createRefreshCookie($request, $refreshToken, $refreshTokenExpiresAt));
    }

    public function clearAuthCookies(Response $response, Request $request): void
    {
        $secure = $this->isSecureCookie($request);
        $response->headers->clearCookie(self::ACCESS_COOKIE, '/api', null, $secure, true, Cookie::SAMESITE_LAX);
        $response->headers->clearCookie(self::REFRESH_COOKIE, '/api/auth', null, $secure, true, Cookie::SAMESITE_LAX);
    }

    private function createAccessCookie(Request $request, string $jwt): Cookie
    {
        return Cookie::create(
            self::ACCESS_COOKIE,
            $jwt,
            time() + self::ACCESS_TOKEN_TTL_SECONDS,
            '/api',
            null,
            $this->isSecureCookie($request),
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    private function createRefreshCookie(Request $request, string $refreshToken, string $expiresAt): Cookie
    {
        return Cookie::create(
            self::REFRESH_COOKIE,
            $refreshToken,
            new \DateTimeImmutable($expiresAt),
            '/api/auth',
            null,
            $this->isSecureCookie($request),
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    private function isSecureCookie(Request $request): bool
    {
        return $request->isSecure() || 'prod' === $this->environment;
    }
}
