<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Http;

use App\Shared\Http\OvhMailboxDiscovery;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OvhMailboxDiscoveryTest extends TestCase
{
    public function testDiscoverReturnsMailboxDataWhenStatusIsOk(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())
            ->method('toArray')
            ->with(false)
            ->willReturn([
                'status' => 'ok',
                'type' => 'MAILHA',
                'webmailUrl' => 'https://roundcube.example',
            ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects(self::once())
            ->method('request')
            ->with('GET', 'https://msservices.eu.ovhapis.com/1.0/webmail/', ['query' => ['email' => 'ada@example.com']])
            ->willReturn($response);

        $discovery = new OvhMailboxDiscovery($client);

        self::assertSame([
            'status' => 'ok',
            'type' => 'MAILHA',
            'webmailUrl' => 'https://roundcube.example',
        ], $discovery->discover('ada@example.com'));
    }

    public function testDiscoverRejectsUnexpectedStatus(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->with(false)->willReturn(['status' => 'error']);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $discovery = new OvhMailboxDiscovery($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OVH mailbox discovery failed.');
        $discovery->discover('ada@example.com');
    }
}
