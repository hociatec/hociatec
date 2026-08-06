<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

final readonly class RateLimitKeyFactory extends \App\Shared\Application\Http\RateLimitKeyFactory
{
    public function forRequest(Request $request, ?string $identity = null): string
    {
        return $this->forClient($request->getClientIp() ?? 'unknown', $identity);
    }
}
