<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Http;

use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class PrivateFileDownloadRateLimiter
{
    public function __construct(
        private RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.private_file_download')]
        private RateLimiterFactory $limiter,
    ) {
    }

    public function isAccepted(Request $request, string $keySuffix): bool
    {
        return $this->limiter
            ->create($this->rateLimitKeys->forRequest($request, $keySuffix))
            ->consume(1)
            ->isAccepted();
    }
}
