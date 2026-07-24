<?php

declare(strict_types=1);

namespace App\Shared\Http;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class RateLimited
{
    public function __construct(
        public string $limiter,
        public int $tokens = 1,
    ) {
        if ($tokens < 1) {
            throw new \InvalidArgumentException('The number of consumed tokens must be positive.');
        }
    }
}
