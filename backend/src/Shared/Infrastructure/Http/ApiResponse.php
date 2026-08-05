<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function success(array $data = [], int $status = JsonResponse::HTTP_OK, ?string $message = null, array $meta = []): JsonResponse
    {
        $payload = [
            'status' => 'success',
            'data' => $data,
            'meta' => $meta,
            'message' => self::normalizeMessage($message),
        ];

        return new JsonResponse($payload, $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function created(array $data = [], ?string $message = null): JsonResponse
    {
        return self::success($data, JsonResponse::HTTP_CREATED, $message);
    }

    public static function successItem(string $key, mixed $value, int $status = JsonResponse::HTTP_OK, ?string $message = null): JsonResponse
    {
        return self::success([$key => $value], $status, $message);
    }

    public static function createdItem(string $key, mixed $value, ?string $message = null): JsonResponse
    {
        return self::created([$key => $value], $message);
    }

    /**
     * @param array<int|string, mixed>                             $items
     * @param array{page:int,perPage:int,total:int,totalPages:int} $meta
     */
    public static function paginated(array $items, array $meta): JsonResponse
    {
        return self::success([
            'items' => $items,
            'meta' => $meta,
        ], meta: $meta);
    }

    /**
     * @param array<string, mixed>|list<string> $details
     */
    public static function error(string $message, int $status = JsonResponse::HTTP_BAD_REQUEST, array $details = [], ?string $code = null): JsonResponse
    {
        $error = [
            'code' => $code ?? self::defaultErrorCode($status),
            'message' => $message,
            'fields' => self::normalizeFields($details),
            'details' => $details,
            'requestId' => null,
        ];

        return new JsonResponse([
            'status' => 'error',
            'error' => $error,
            'code' => $error['code'],
            'message' => $message,
            'details' => $details,
        ], $status);
    }

    public static function internalError(string $message = 'Une erreur interne est survenue.'): JsonResponse
    {
        unset($message);

        return self::error('Une erreur interne est survenue.', JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    private static function defaultErrorCode(int $status): string
    {
        return match ($status) {
            JsonResponse::HTTP_BAD_REQUEST => 'BAD_REQUEST',
            JsonResponse::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
            JsonResponse::HTTP_FORBIDDEN => 'FORBIDDEN',
            JsonResponse::HTTP_NOT_FOUND => 'NOT_FOUND',
            JsonResponse::HTTP_CONFLICT => 'CONFLICT',
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY => 'UNPROCESSABLE_ENTITY',
            JsonResponse::HTTP_TOO_MANY_REQUESTS => 'TOO_MANY_REQUESTS',
            default => $status >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR ? 'INTERNAL_ERROR' : 'REQUEST_ERROR',
        };
    }

    private static function normalizeMessage(?string $message): ?string
    {
        if (null === $message) {
            return null;
        }

        $normalized = trim($message);

        return '' === $normalized ? null : $normalized;
    }

    /**
     * @param array<string, mixed>|list<string> $details
     *
     * @return array<string, list<string>>
     */
    private static function normalizeFields(array $details): array
    {
        $fields = [];
        foreach ($details as $field => $messages) {
            if (!is_string($field)) {
                continue;
            }

            $fields[$field] = array_values(array_map('strval', is_array($messages) ? $messages : [$messages]));
        }

        return $fields;
    }
}
