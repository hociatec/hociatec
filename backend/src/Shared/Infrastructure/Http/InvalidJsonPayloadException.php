<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Exception\AbstractPublicApiException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidJsonPayloadException extends AbstractPublicApiException implements PublicApiException
{
    public function __construct(string $message = 'Payload JSON invalide.')
    {
        parent::__construct($message, Response::HTTP_BAD_REQUEST, $message, 'INVALID_JSON_PAYLOAD');
    }
}
