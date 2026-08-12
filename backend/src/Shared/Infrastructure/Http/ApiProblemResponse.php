<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Exception\ApiProblemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ApiProblemResponse
{
    private function __construct()
    {
    }

    /**
     * @param list<string>|array<string, mixed> $details
     */
    public static function fromInvalidArgument(
        \InvalidArgumentException $exception,
        int $status = JsonResponse::HTTP_BAD_REQUEST,
        array $details = [],
    ): JsonResponse {
        if ($exception instanceof ApiProblemException) {
            return self::fromThrowable($exception, null, $status, $details);
        }

        return ApiResponse::error(
            self::defaultMessage($status),
            $status,
            $details,
            self::statusCodeToErrorCode($status),
        );
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
        if ($exception instanceof ApiProblemException) {
            $status = $fallbackStatus ?? $exception->getStatusCode();
            $message = $fallbackMessage ?? $exception->publicMessage();
            $payloadDetails = [] !== $details ? $details : $exception->details();
            $code = null !== $fallbackStatus && $fallbackStatus !== $exception->getStatusCode()
                ? self::statusCodeToErrorCode($status)
                : $exception->errorCode();

            return ApiResponse::error(
                $message,
                $status,
                $payloadDetails,
                $code,
            );
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $fallbackStatus ?? $exception->getStatusCode();

            return ApiResponse::error(
                $fallbackMessage ?? self::defaultMessage($status),
                $status,
                $details,
                self::statusCodeToErrorCode($status),
            );
        }

        if ($exception instanceof \DomainException) {
            return ApiResponse::error(
                $fallbackMessage ?? 'Requête impossible.',
                $fallbackStatus ?? JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                $details,
                self::statusCodeToErrorCode($fallbackStatus ?? JsonResponse::HTTP_UNPROCESSABLE_ENTITY),
            );
        }

        if ($exception instanceof \InvalidArgumentException) {
            return ApiResponse::error(
                $fallbackMessage ?? 'Requête invalide.',
                $fallbackStatus ?? JsonResponse::HTTP_BAD_REQUEST,
                $details,
                self::statusCodeToErrorCode($fallbackStatus ?? JsonResponse::HTTP_BAD_REQUEST),
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
