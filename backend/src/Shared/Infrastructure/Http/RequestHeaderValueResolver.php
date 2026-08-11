<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

final class RequestHeaderValueResolver
{
    public function nonEmptyString(Request $request, string $headerName): ?string
    {
        $value = $request->headers->get($headerName);

        if (is_string($value) && '' !== $value) {
            return $value;
        }

        return null;
    }
}
