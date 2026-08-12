<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

abstract class AbstractApiProblemException extends \RuntimeException implements ApiProblemException
{
    private const HTTP_BAD_REQUEST = 400;
    private const HTTP_UNAUTHORIZED = 401;
    private const HTTP_FORBIDDEN = 403;
    private const HTTP_NOT_FOUND = 404;
    private const HTTP_CONFLICT = 409;
    private const HTTP_UNPROCESSABLE_ENTITY = 422;
    private const HTTP_TOO_MANY_REQUESTS = 429;
    private const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * @param array<string, mixed>|list<string> $details
     */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly ?string $publicMessage = null,
        private readonly ?string $errorCode = null,
        private readonly array $details = [],
        int $exceptionCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $exceptionCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function publicMessage(): string
    {
        return $this->publicMessage ?? $this->getMessage();
    }

    public function errorCode(): string
    {
        return $this->errorCode ?? self::defaultErrorCode($this->statusCode);
    }

    public function details(): array
    {
        return $this->details;
    }

    private static function defaultErrorCode(int $statusCode): string
    {
        return match ($statusCode) {
            self::HTTP_BAD_REQUEST => 'BAD_REQUEST',
            self::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
            self::HTTP_FORBIDDEN => 'FORBIDDEN',
            self::HTTP_NOT_FOUND => 'NOT_FOUND',
            self::HTTP_CONFLICT => 'CONFLICT',
            self::HTTP_UNPROCESSABLE_ENTITY => 'UNPROCESSABLE_ENTITY',
            self::HTTP_TOO_MANY_REQUESTS => 'TOO_MANY_REQUESTS',
            default => $statusCode >= self::HTTP_INTERNAL_SERVER_ERROR ? 'INTERNAL_ERROR' : 'REQUEST_ERROR',
        };
    }
}
