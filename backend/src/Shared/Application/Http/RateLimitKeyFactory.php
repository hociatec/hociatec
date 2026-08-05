<?php

declare(strict_types=1);

namespace App\Shared\Application\Http;

use Symfony\Component\HttpFoundation\Request;

readonly class RateLimitKeyFactory
{
    public function forRequest(Request $request, ?string $identity = null): string
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $identity = null !== $identity ? strtolower(trim($identity)) : '';

        if ('' === $identity) {
            return 'ip:'.$ip;
        }

        return 'ip:'.$ip.':identity:'.hash('sha256', $identity);
    }
}
