<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data = [], int $status = JsonResponse::HTTP_OK, ?string $message = null): JsonResponse
    {
        $payload = [
            'status' => 'success',
            'data' => $data,
        ];
        if (null !== $message && '' !== trim($message)) {
            $payload['message'] = $message;
        }

        return new JsonResponse($payload, $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function created(array $data = [], ?string $message = null): JsonResponse
    {
        return self::success($data, JsonResponse::HTTP_CREATED, $message);
    }

    /**
     * @param list<mixed>                                          $items
     * @param array{page:int,perPage:int,total:int,totalPages:int} $meta
     */
    public static function paginated(array $items, array $meta): JsonResponse
    {
        return self::success([
            'items' => $items,
            'meta' => $meta,
        ]);
    }

    /**
     * @param array<string, mixed>|list<string> $details
     */
    public static function error(string $message, int $status = JsonResponse::HTTP_BAD_REQUEST, array $details = [], ?string $code = null): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'code' => $code ?? self::defaultErrorCode($status),
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
}
