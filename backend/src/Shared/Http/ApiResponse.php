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
    public static function success(array $data = [], int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
        ], $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function created(array $data = []): JsonResponse
    {
        return self::success($data, JsonResponse::HTTP_CREATED);
    }

    /**
     * @param list<mixed> $items
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
    public static function error(string $message, int $status = JsonResponse::HTTP_BAD_REQUEST, array $details = []): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $message,
            'details' => $details,
        ], $status);
    }
}
