<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class ApiValidationException extends \RuntimeException implements ApiProblemException, PublicApiException
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        string $message,
        public readonly array $details,
        public readonly int $statusCode = 422,
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
