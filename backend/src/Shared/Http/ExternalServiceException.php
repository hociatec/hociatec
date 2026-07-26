<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpFoundation\Response;

final class ExternalServiceException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_GATEWAY;
    }
}
