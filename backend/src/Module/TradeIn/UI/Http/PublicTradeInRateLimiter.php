<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Http;

use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class PublicTradeInRateLimiter
{
    public function __construct(
        private RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.public_api')]
        private RateLimiterFactory $limiter,
    ) {
    }

    public function isAccepted(Request $request, ?string $email): bool
    {
        return $this->limiter
            ->create($this->rateLimitKeys->forRequest($request, $email))
            ->consume(1)
            ->isAccepted();
    }
}
