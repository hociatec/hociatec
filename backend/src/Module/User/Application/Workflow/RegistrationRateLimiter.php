<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RegistrationRateLimiter
{
    public function __construct(
        private RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.auth_register')]
        private RateLimiterFactory $registrationLimiter,
    ) {
    }

    public function isAccepted(Request $request, ?string $email): bool
    {
        return $this->registrationLimiter
            ->create($this->rateLimitKeys->forRequest($request, $email))
            ->consume(1)
            ->isAccepted();
    }
}
