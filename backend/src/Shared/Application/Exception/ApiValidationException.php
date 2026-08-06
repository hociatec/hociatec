<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ApiValidationException extends \RuntimeException implements ApiProblemException, PublicApiException
{
    public function __construct(
        string $message,
        public readonly array $details,
        public readonly int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function publicMessage(): string
    {
        return $this->getMessage();
    }
}

