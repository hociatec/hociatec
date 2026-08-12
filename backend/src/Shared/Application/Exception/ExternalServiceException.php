<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class ExternalServiceException extends AbstractPublicApiException
{
    private const HTTP_BAD_GATEWAY = 502;

    public function __construct(string $message = 'Service externe indisponible.', ?string $publicMessage = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            $message,
            self::HTTP_BAD_GATEWAY,
            $publicMessage ?? 'Service externe momentanément indisponible.',
            'EXTERNAL_SERVICE_UNAVAILABLE',
            exceptionCode: $code,
            previous: $previous,
        );
    }
}
