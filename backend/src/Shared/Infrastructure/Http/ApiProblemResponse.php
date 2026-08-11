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
            return ApiResponse::validation($exception->getMessage(), $exception->details);
        }

        if ($exception instanceof PublicApiException) {
            return ApiResponse::error(
                $exception->publicMessage(),
                $exception->getStatusCode(),
                $details,
                self::publicExceptionCode($exception),
            );
        }

        if ($exception instanceof ApiProblemException) {
            return ApiResponse::error(
                $fallbackMessage ?? self::defaultMessage($exception->getStatusCode()),
                $fallbackStatus ?? $exception->getStatusCode(),
                $details,
                self::statusCodeToErrorCode($fallbackStatus ?? $exception->getStatusCode()),
            );
        }

        if ($exception instanceof \DomainException) {
            return ApiResponse::unprocessable(
                $fallbackMessage ?? 'Requête impossible.',
                $details,
            );
        }

        if ($exception instanceof \InvalidArgumentException) {
            return ApiResponse::badRequest(
                $fallbackMessage ?? 'Requête invalide.',
                $details,
            );
        }

        return ApiResponse::error(
            $fallbackMessage ?? 'Une erreur interne est survenue.',
            $fallbackStatus ?? JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            $details,
            'INTERNAL_ERROR',
        );
    }

    private static function defaultMessage(int $status): string
    {
        return $status >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            ? 'Une erreur interne est survenue.'
            : 'Requête impossible.';
    }

    private static function publicExceptionCode(PublicApiException $exception): string
    {
        return match ($exception->getStatusCode()) {
            JsonResponse::HTTP_BAD_REQUEST => 'BAD_REQUEST',
            JsonResponse::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
            JsonResponse::HTTP_FORBIDDEN => 'FORBIDDEN',
            JsonResponse::HTTP_NOT_FOUND => 'NOT_FOUND',
            JsonResponse::HTTP_CONFLICT => 'CONFLICT',
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY => $exception instanceof ApiValidationException ? 'VALIDATION_ERROR' : 'UNPROCESSABLE_ENTITY',
            default => $exception->getStatusCode() >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR ? 'INTERNAL_ERROR' : 'REQUEST_ERROR',
        };
    }

    private static function statusCodeToErrorCode(int $statusCode): string
    {
        return match ($statusCode) {
            JsonResponse::HTTP_BAD_REQUEST => 'BAD_REQUEST',
            JsonResponse::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
            JsonResponse::HTTP_FORBIDDEN => 'FORBIDDEN',
            JsonResponse::HTTP_NOT_FOUND => 'NOT_FOUND',
            JsonResponse::HTTP_CONFLICT => 'CONFLICT',
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY => 'UNPROCESSABLE_ENTITY',
            JsonResponse::HTTP_TOO_MANY_REQUESTS => 'TOO_MANY_REQUESTS',
            default => $statusCode >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR ? 'INTERNAL_ERROR' : 'REQUEST_ERROR',
        };
    }
}
