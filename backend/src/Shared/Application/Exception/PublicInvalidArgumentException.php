<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class PublicInvalidArgumentException extends \InvalidArgumentException implements ApiProblemException
{
    private const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * @param array<string, mixed>|list<string> $details
     */
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        private readonly ?string $errorCode = null,
        private readonly array $details = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function publicMessage(): string
    {
        return $this->message;
    }

    public function errorCode(): string
    {
        return $this->errorCode ?? ($this->statusCode >= self::HTTP_INTERNAL_SERVER_ERROR ? 'INTERNAL_ERROR' : 'BAD_REQUEST');
    }

    public function details(): array
    {
        return $this->details;
    }
}
