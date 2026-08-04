<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Response;

final class InvalidJsonPayloadException extends \RuntimeException implements PublicApiException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function publicMessage(): string
    {
        return $this->getMessage();
    }
}
