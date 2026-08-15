<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Http;

use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RefreshTokenRequestRateLimiter
{
    public function __construct(
        private RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.auth_refresh')]
        private RateLimiterFactory $refreshLimiter,
    ) {
    }

    public function consume(Request $request, string $refreshToken): RateLimit
    {
        return $this->refreshLimiter
            ->create($this->rateLimitKeys->forRequest($request, $this->refreshTokenSelector($refreshToken)))
            ->consume(1);
    }

    private function refreshTokenSelector(string $refreshToken): ?string
    {
        $selector = explode('.', $refreshToken, 2)[0];

        return '' !== $selector ? $selector : null;
    }
}
