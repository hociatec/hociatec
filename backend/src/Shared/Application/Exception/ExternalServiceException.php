<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class ExternalServiceException extends \RuntimeException implements PublicApiException
{
    private const HTTP_BAD_GATEWAY = 502;

    private string $publicMessage;

    public function __construct(string $message = 'Service externe indisponible.', ?string $publicMessage = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->publicMessage = $publicMessage ?? 'Service externe momentanément indisponible.';
    }

    public function getStatusCode(): int
    {
        return self::HTTP_BAD_GATEWAY;
    }

    public function publicMessage(): string
    {
        return $this->publicMessage;
    }
}
