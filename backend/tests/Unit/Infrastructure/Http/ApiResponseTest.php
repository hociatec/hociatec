<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ApiResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponseTest extends TestCase
{
    public function testSuccessBuildsTheExpectedPayload(): void
    {
        $response = ApiResponse::success(['foo' => 'bar'], JsonResponse::HTTP_ACCEPTED, 'Done');

        self::assertSame(JsonResponse::HTTP_ACCEPTED, $response->getStatusCode());
        self::assertSame([
            'status' => 'success',
            'data' => ['foo' => 'bar'],
            'meta' => [],
            'message' => 'Done',
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testSuccessNormalizesBlankMessage(): void
    {
        $response = ApiResponse::success(['foo' => 'bar'], message: '   ');

        self::assertSame([
            'status' => 'success',
            'data' => ['foo' => 'bar'],
            'meta' => [],
            'message' => null,
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCreatedUsesCreatedStatus(): void
    {
        $response = ApiResponse::created(['id' => 42], 'Created');

        self::assertSame(JsonResponse::HTTP_CREATED, $response->getStatusCode());
        self::assertSame([
            'status' => 'success',
            'data' => ['id' => 42],
            'meta' => [],
            'message' => 'Created',
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testItemHelpersWrapSingleKeyPayloads(): void
    {
        $response = ApiResponse::successItem('item', ['id' => 42]);
        $created = ApiResponse::createdItem('id', 42, 'Created');

        self::assertSame(['item' => ['id' => 42]], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']);
        self::assertSame(JsonResponse::HTTP_CREATED, $created->getStatusCode());
        self::assertSame(['id' => 42], json_decode((string) $created->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']);
    }

    public function testPaginatedWrapsItemsAndMeta(): void
    {
        $response = ApiResponse::paginated(
            [['id' => 1], ['id' => 2]],
            ['page' => 2, 'perPage' => 10, 'total' => 25, 'totalPages' => 3],
        );

        self::assertSame([
            'status' => 'success',
            'data' => [
                'items' => [['id' => 1], ['id' => 2]],
                'meta' => ['page' => 2, 'perPage' => 10, 'total' => 25, 'totalPages' => 3],
            ],
            'meta' => ['page' => 2, 'perPage' => 10, 'total' => 25, 'totalPages' => 3],
            'message' => null,
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testErrorBuildsTheExpectedPayload(): void
    {
        $response = ApiResponse::error('Broken', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, ['field' => 'name']);

        self::assertSame(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame([
            'status' => 'error',
            'error' => [
                'code' => 'UNPROCESSABLE_ENTITY',
                'message' => 'Broken',
                'fields' => ['field' => ['name']],
                'details' => ['field' => 'name'],
                'requestId' => null,
            ],
            'code' => 'UNPROCESSABLE_ENTITY',
            'message' => 'Broken',
            'details' => ['field' => 'name'],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));

        $coded = ApiResponse::error('Invalid RIB.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, [], 'TRADE_IN_INVALID_RIB');
        self::assertSame('TRADE_IN_INVALID_RIB', json_decode((string) $coded->getContent(), true, 512, JSON_THROW_ON_ERROR)['code']);
    }

    public function testInternalErrorUsesDefaultMessageAndStatus(): void
    {
        $response = ApiResponse::internalError('SQLSTATE /home/app secret');

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame([
            'status' => 'error',
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Une erreur interne est survenue.',
                'fields' => [],
                'details' => [],
                'requestId' => null,
            ],
            'code' => 'INTERNAL_ERROR',
            'message' => 'Une erreur interne est survenue.',
            'details' => [],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
