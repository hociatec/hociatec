<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\LibreTranslateClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LibreTranslateClientTest extends TestCase
{
    public function testItRetriesTransportFailuresAndSucceedsOnSecondAttempt(): void
    {
        $calls = 0;
        $client = new LibreTranslateClient(new MockHttpClient(function () use (&$calls): MockResponse {
            ++$calls;

            if (1 === $calls) {
                throw new TransportException('temporary failure');
            }

            return new MockResponse(json_encode(['translatedText' => 'Bonjour'], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        }), 'https://translate.example.test/translate');

        self::assertSame('Bonjour', $client->translate('Hello', 'en', 'fr'));
        self::assertSame(2, $calls);
    }

    public function testItOpensCircuitBreakerAfterRepeatedFailuresAndSkipsBlockedEndpoint(): void
    {
        $primaryCalls = 0;
        $fallbackCalls = 0;
        $client = new LibreTranslateClient(new MockHttpClient(function (string $method, string $url) use (&$primaryCalls, &$fallbackCalls): MockResponse {
            if (str_contains($url, 'primary')) {
                ++$primaryCalls;

                return new MockResponse('temporary failure', ['http_code' => 503]);
            }

            ++$fallbackCalls;

            return new MockResponse(json_encode(['translatedText' => 'Bonjour'], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        }), 'https://primary.example.test/translate', 'https://fallback.example.test/translate');

        self::assertSame('Bonjour', $client->translate('Hello', 'en', 'fr'));
        self::assertSame(2, $primaryCalls);
        self::assertSame(1, $fallbackCalls);

        self::assertSame('Bonjour', $client->translate('Hi', 'en', 'fr'));
        self::assertSame(2, $primaryCalls);
        self::assertSame(2, $fallbackCalls);
    }
}
