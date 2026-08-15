<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

interface AccessSessionLocationResolver
{
    public function resolve(Request $request, ?string $clientIp): ?string;
}
