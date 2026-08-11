<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

abstract class AbstractPublicApiException extends \RuntimeException implements PublicApiException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
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
