<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\JsonPayload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class JsonPayloadTest extends MiscSupportTestCase
{
    public function testJsonPayloadDecodeHandlesValidAndInvalidPayloads(): void
    {
        self::assertSame([], JsonPayload::decode(new Request(content: '   ')));
        self::assertSame(['name' => 'Ada'], JsonPayload::decode(new Request(content: '{"name":"Ada"}')));

        try {
            JsonPayload::decode(new Request(content: '{"name":'));
            self::fail('Expected invalid JSON exception.');
        } catch (InvalidJsonPayloadException $exception) {
            self::assertSame('Payload JSON invalide.', $exception->getMessage());
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }

        try {
            JsonPayload::decode(new Request(content: '[1,2,3]'));
            self::fail('Expected object payload exception.');
        } catch (InvalidJsonPayloadException $exception) {
            self::assertSame('Le payload JSON doit être un objet.', $exception->getMessage());
        }

        try {
            JsonPayload::decode(new Request(content: str_repeat('a', 1_048_577)));
            self::fail('Expected oversized payload exception.');
        } catch (InvalidJsonPayloadException $exception) {
            self::assertSame('Payload trop volumineux.', $exception->getMessage());
        }

        $this->coverPrivateConstructor(JsonPayload::class);
    }
}
