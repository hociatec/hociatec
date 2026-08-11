<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Exception\ApiProblemException;
use App\Shared\Application\Exception\ApiValidationException;
use App\Shared\Application\Exception\PublicApiException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiProblemResponse
{
    private function __construct()
    {
    }

    /**
     * @param list<string>|array<string, mixed> $details
     */
    public static function fromThrowable(
        \Throwable $exception,
        ?string $fallbackMessage = null,
        ?int $fallbackStatus = null,
        array $details = [],
    ): JsonResponse {
        if ($exception instanceof ApiValidationException) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode, $exception->details);
        }

        if ($exception instanceof PublicApiException) {
            return ApiResponse::error($exception->publicMessage(), $exception->getStatusCode(), $details);
        }

        if ($exception instanceof ApiProblemException) {
            return ApiResponse::error(
                $fallbackMessage ?? self::defaultMessage($exception->getStatusCode()),
                $fallbackStatus ?? $exception->getStatusCode(),
                $details,
            );
        }

        if ($exception instanceof \DomainException) {
            return ApiResponse::error(
                $fallbackMessage ?? 'Requête impossible.',
                $fallbackStatus ?? JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                $details,
            );
        }

        if ($exception instanceof \InvalidArgumentException) {
            return ApiResponse::error(
                $fallbackMessage ?? 'Requête invalide.',
                $fallbackStatus ?? JsonResponse::HTTP_BAD_REQUEST,
                $details,
            );
        }

        return ApiResponse::error(
            $fallbackMessage ?? 'Une erreur interne est survenue.',
            $fallbackStatus ?? JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            $details,
        );
    }

    private static function defaultMessage(int $status): string
    {
        return $status >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ? 'Une erreur interne est survenue.'
            : 'Requête impossible.';
    }
}
