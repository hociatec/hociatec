<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface AuthCookieResponseWriter
{
    public const ACCESS_COOKIE = 'hociatec_access';
    public const REFRESH_COOKIE = 'hociatec_refresh';

    public function attachLoginCookies(Response $response, Request $request, string $jwt, string $refreshToken, string $refreshTokenExpiresAt): void;

    public function clearAuthCookies(Response $response, Request $request): void;
}
