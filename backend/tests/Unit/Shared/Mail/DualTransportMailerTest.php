<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Mail;

use App\Shared\Http\OvhMailboxDiscovery;
use App\Shared\Http\OvhRoundcubeMailer;
use App\Shared\Http\RoundcubeCredentialsProvider;
use App\Shared\Http\RoundcubeFormParser;
use App\Shared\Http\RoundcubeHttpSession;
use App\Shared\Mail\DualTransportMailer;
use App\Shared\Mail\MailDeliveryException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class DualTransportMailerTest extends TestCase
{
    public function testSendStopsWhenPrimaryTransportSucceeds(): void
    {
        $fallback = $this->createMock(MailerInterface::class);
        $fallback->expects(self::never())->method('send');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');
        $logger->expects(self::never())->method('error');

        $mailer = new DualTransportMailer($this->successfulPrimaryMailer(), $fallback, $logger);
        $mailer->send('target@example.com', 'Sujet', 'Corps', new Email(), 'checkout', 'reply@example.com');

        self::assertTrue(true);
    }

    public function testSendFallsBackWhenPrimaryTransportFails(): void
    {
        $fallback = $this->createMock(MailerInterface::class);
        $fallback->expects(self::once())->method('send')->with(self::isInstanceOf(Email::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Primary email transport failed.',
                self::callback(static fn (array $context): bool => 'contact' === $context['context']
                    && $context['exception'] instanceof \RuntimeException),
            );
        $logger->expects(self::never())->method('error');

        $mailer = new DualTransportMailer($this->failingPrimaryMailer(), $fallback, $logger);
        $mailer->send('target@example.com', 'Sujet', 'Corps', new Email(), 'contact');
    }

    public function testSendThrowsMailDeliveryExceptionWhenBothTransportsFail(): void
    {
        $fallback = $this->createMock(MailerInterface::class);
        $fallback->expects(self::once())
            ->method('send')
            ->willThrowException(new TransportException('smtp down'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Fallback email transport failed.',
                self::callback(static fn (array $context): bool => 'quote' === $context['context']
                    && $context['exception'] instanceof TransportException),
            );

        $mailer = new DualTransportMailer($this->failingPrimaryMailer(), $fallback, $logger);

        $this->expectException(MailDeliveryException::class);
        $this->expectExceptionMessage('Email delivery failed for quote.');
        $mailer->send('target@example.com', 'Sujet', 'Corps', new Email(), 'quote');
    }

    private function failingPrimaryMailer(): OvhRoundcubeMailer
    {
        return new OvhRoundcubeMailer(
            new RoundcubeCredentialsProvider('', '', ''),
            new OvhMailboxDiscovery($this->createMock(HttpClientInterface::class)),
            new RoundcubeHttpSession($this->createMock(HttpClientInterface::class)),
            new RoundcubeFormParser(),
        );
    }

    private function successfulPrimaryMailer(): OvhRoundcubeMailer
    {
        $discoveryResponse = $this->createMock(ResponseInterface::class);
        $discoveryResponse->method('toArray')->with(false)->willReturn([
            'status' => 'ok',
            'type' => 'MAILHA',
            'webmailUrl' => 'https://mail.example/roundcube/',
        ]);

        $discoveryClient = $this->createMock(HttpClientInterface::class);
        $discoveryClient->method('request')->willReturn($discoveryResponse);

        $sessionClient = $this->createMock(HttpClientInterface::class);
        $sessionClient->method('request')->willReturnOnConsecutiveCalls(
            $this->response(content: '<input name="_token" value="login-token">'),
            $this->response(statusCode: 302, headers: ['location' => ['/?_task=mail']]),
            $this->response(content: 'inbox'),
            $this->response(content: '<input name="_token" value="compose-token"><input name="_id" value="compose-1"><select name="_from"><option value="9" selected>Main</option></select>'),
            $this->response(content: 'sent_successfully'),
        );

        return new OvhRoundcubeMailer(
            new RoundcubeCredentialsProvider('ada@example.com', 'secret', ''),
            new OvhMailboxDiscovery($discoveryClient),
            new RoundcubeHttpSession($sessionClient),
            new RoundcubeFormParser(),
        );
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function response(int $statusCode = 200, array $headers = [], string $content = ''): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getHeaders')->with(false)->willReturn($headers);
        $response->method('getContent')->willReturn($content);

        return $response;
    }
}
