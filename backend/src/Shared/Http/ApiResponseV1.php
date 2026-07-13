<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponseV1
{
    private function __construct() {}

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function ok(array $data = [], ?string $message = null, array $meta = []): JsonResponse
    {
        return new JsonResponse([
            'code' => 'ok',
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function created(array $data = [], ?string $message = null, array $meta = []): JsonResponse
    {
        return new JsonResponse([
            'code' => 'ok',
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * @param list<array<string, mixed>|string> $errors
     * @param array<string, mixed> $meta
     */
    public static function error(string $code, string $message, int $httpStatus = JsonResponse::HTTP_BAD_REQUEST, array $errors = [], array $meta = []): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'meta' => $meta,
        ], $httpStatus);
    }
}

