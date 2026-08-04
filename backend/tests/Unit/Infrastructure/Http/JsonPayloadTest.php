<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http;

use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\JsonPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class JsonPayloadTest extends TestCase
{
    public function testDecodeHandlesEmptyAndValidObjectPayloads(): void
    {
        self::assertSame([], JsonPayload::decode(Request::create('/', 'POST', server: [], content: '   ')));
        self::assertSame(
            ['name' => 'Ada', 'nested' => ['ok' => true]],
            JsonPayload::decode(Request::create('/', 'POST', server: [], content: '{"name":"Ada","nested":{"ok":true}}')),
        );
    }

    public function testDecodeRejectsInvalidJsonListPayloadAndOversizedBody(): void
    {
        $cases = [
            ['{bad', 'Payload JSON invalide.'],
            ['["a","b"]', 'Le payload JSON doit être un objet.'],
            [str_repeat('a', 1_048_577), 'Payload trop volumineux.'],
        ];

        foreach ($cases as [$content, $message]) {
            try {
                JsonPayload::decode(Request::create('/', 'POST', server: [], content: $content));
                self::fail('Expected invalid JSON payload to throw.');
            } catch (InvalidJsonPayloadException $exception) {
                self::assertSame($message, $exception->getMessage());
            }
        }
    }
}
