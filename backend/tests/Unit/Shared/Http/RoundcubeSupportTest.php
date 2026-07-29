<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Http;

use App\Shared\Http\OvhMailboxDiscovery;
use App\Shared\Http\OvhRoundcubeMailer;
use App\Shared\Http\RoundcubeCredentialsProvider;
use App\Shared\Http\RoundcubeFormParser;
use App\Shared\Http\RoundcubeHttpSession;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class RoundcubeSupportTest extends TestCase
{
    public function testFormParserReadsInputAndSelectedOptionValues(): void
    {
        $parser = new RoundcubeFormParser();
        $html = <<<'HTML'
<html><body>
    <form>
        <input name="_token" value="token-123" />
        <select name="_from">
            <option value="1">One</option>
            <option value="2" selected>Two</option>
        </select>
    </form>
</body></html>
HTML;

        self::assertSame('token-123', $parser->inputValue($html, '_token'));
        self::assertSame('2', $parser->selectedOptionValue($html, '_from'));
        self::assertNull($parser->inputValue($html, '_missing'));
    }

    public function testFormParserFallsBackToFirstOptionWhenNoneIsSelected(): void
    {
        $parser = new RoundcubeFormParser();
        $html = '<select name="_from"><option value="7">Main</option><option value="8">Alt</option></select>';

        self::assertSame('7', $parser->selectedOptionValue($html, '_from'));
        self::assertNull($parser->selectedOptionValue('<html></html>', '_from'));
    }

    public function testRoundcubeHttpSessionStoresCookiesAndForwardsCookieHeader(): void
    {
        $response = $this->response(statusCode: 200, headers: [
            'set-cookie' => [
                'invalid-cookie',
                'sid=def; Path=/',
                'obsolete=-del-; Path=/',
                'lang=fr; Path=/',
            ],
        ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'https://mail.example/action',
                self::callback(static function (array $options): bool {
                    return 'sid=abc; obsolete=1' === ($options['headers']['Cookie'] ?? null)
                        && 0 === $options['max_redirects']
                        && 20 === $options['timeout'];
                }),
            )
            ->willReturn($response);

        $session = new RoundcubeHttpSession($client);
        $cookies = ['sid' => 'abc', 'obsolete' => '1'];

        $returned = $session->request('POST', 'https://mail.example/action', $cookies);

        self::assertSame($response, $returned);
        self::assertSame(['sid' => 'def', 'lang' => 'fr'], $cookies);
    }

    public function testRoundcubeHttpSessionRejectsUnexpectedStatusCodes(): void
    {
        $session = new RoundcubeHttpSession($this->clientReturning($this->response(statusCode: 500)));
        $cookies = [];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected HTTP status 500 for GET https://mail.example/login.');
        $session->request('GET', 'https://mail.example/login', $cookies);
    }

    public function testRoundcubeHttpSessionWrapsTransportExceptions(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getHeaders')->with(false)->willReturn([]);
        $response->method('getStatusCode')->willThrowException(new class ('boom') extends \RuntimeException implements ExceptionInterface {
        });

        $session = new RoundcubeHttpSession($this->clientReturning($response));
        $cookies = [];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP request failed for GET https://mail.example/login.');
        $session->request('GET', 'https://mail.example/login', $cookies);
    }

    public function testRoundcubeHttpSessionResolvesAbsoluteAndRelativeUrls(): void
    {
        $session = new RoundcubeHttpSession($this->createMock(HttpClientInterface::class));

        self::assertSame('https://other.example/path', $session->resolveUrl('https://mail.example/base', 'https://other.example/path'));
        self::assertSame('https://mail.example/?_task=mail', $session->resolveUrl('https://mail.example/base', '/?_task=mail'));
    }

    public function testOvhRoundcubeMailerSupportsConfiguredMailboxOnlyWhenCredentialsExist(): void
    {
        $withCredentials = new OvhRoundcubeMailer(
            new RoundcubeCredentialsProvider('ada@example.com', 'secret', ''),
            new OvhMailboxDiscovery($this->createMock(HttpClientInterface::class)),
            new RoundcubeHttpSession($this->createMock(HttpClientInterface::class)),
            new RoundcubeFormParser(),
        );
        $withoutCredentials = new OvhRoundcubeMailer(
            new RoundcubeCredentialsProvider('', '', ''),
            new OvhMailboxDiscovery($this->createMock(HttpClientInterface::class)),
            new RoundcubeHttpSession($this->createMock(HttpClientInterface::class)),
            new RoundcubeFormParser(),
        );

        self::assertTrue($withCredentials->supportsConfiguredMailbox());
        self::assertFalse($withoutCredentials->supportsConfiguredMailbox());
    }

    public function testOvhRoundcubeMailerRejectsMissingCredentialsBeforeAnyRequest(): void
    {
        $discoveryClient = $this->createMock(HttpClientInterface::class);
        $discoveryClient->expects(self::never())->method('request');

        $mailer = new OvhRoundcubeMailer(
            new RoundcubeCredentialsProvider('', '', ''),
            new OvhMailboxDiscovery($discoveryClient),
            new RoundcubeHttpSession($this->createMock(HttpClientInterface::class)),
            new RoundcubeFormParser(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OVH mailbox credentials are not configured.');
        $mailer->send('target@example.com', 'Sujet', 'Message');
    }

    public function testOvhRoundcubeMailerSendsMessageThroughRoundcube(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'MAILHA', 'webmailUrl' => 'https://mail.example/roundcube/'],
            [
                $this->response(content: '<input name="_token" value="login-token">'),
                $this->response(statusCode: 302, headers: ['location' => ['/?_task=mail']]),
                $this->response(content: 'inbox'),
                $this->response(content: '<input name="_token" value="compose-token"><input name="_id" value="compose-1"><select name="_from"><option value="9" selected>Main</option></select>'),
                $this->response(content: 'sent_successfully'),
            ],
        );

        $mailer->send('target@example.com', 'Sujet', 'Contenu', 'reply@example.com');

        self::assertTrue(true);
    }

    public function testOvhRoundcubeMailerRejectsUnsupportedMailboxEndpoint(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'MAILHA', 'webmailUrl' => 'https://mail.example/owa/'],
            [],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported OVH webmail endpoint.');
        $mailer->send('target@example.com', 'Sujet', 'Contenu');
    }

    public function testOvhRoundcubeMailerRejectsUnsupportedMailboxType(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'EXCHANGE', 'webmailUrl' => 'https://mail.example/roundcube/'],
            [],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported OVH mailbox type.');
        $mailer->send('target@example.com', 'Sujet', 'Contenu');
    }

    public function testOvhRoundcubeMailerRequiresLoginToken(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'MAILHA', 'webmailUrl' => 'https://mail.example/roundcube/'],
            [$this->response(content: '<html></html>')],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to read Roundcube login token.');
        $mailer->send('target@example.com', 'Sujet', 'Contenu');
    }

    public function testOvhRoundcubeMailerRejectsLoginFailurePage(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'MAILHA', 'webmailUrl' => 'https://mail.example/roundcube/'],
            [
                $this->response(content: '<input name="_token" value="login-token">'),
                $this->response(content: '<a href="?_task=login"></a><a href="?_action=login"></a>'),
            ],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Roundcube login failed.');
        $mailer->send('target@example.com', 'Sujet', 'Contenu');
    }

    public function testOvhRoundcubeMailerRequiresComposeFormFields(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'MAILHA', 'webmailUrl' => 'https://mail.example/roundcube/'],
            [
                $this->response(content: '<input name="_token" value="login-token">'),
                $this->response(statusCode: 302, headers: ['location' => ['/?_task=mail']]),
                $this->response(content: 'inbox'),
                $this->response(content: '<input name="_token" value="compose-token">'),
            ],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to prepare Roundcube compose form.');
        $mailer->send('target@example.com', 'Sujet', 'Contenu');
    }

    public function testOvhRoundcubeMailerFollowsComposeRedirectBeforeReadingForm(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'MAILHA', 'webmailUrl' => 'https://mail.example/roundcube/'],
            [
                $this->response(content: '<input name="_token" value="login-token">'),
                $this->response(statusCode: 302, headers: ['location' => ['/?_task=mail']]),
                $this->response(content: 'inbox'),
                $this->response(statusCode: 302, headers: ['location' => ['/compose?id=1']]),
                $this->response(content: '<input name="_token" value="compose-token"><input name="_id" value="compose-1"><select name="_from"><option value="9" selected>Main</option></select>'),
                $this->response(content: 'sent_successfully'),
            ],
        );

        $mailer->send('target@example.com', 'Sujet', 'Contenu');
        self::assertTrue(true);
    }

    public function testOvhRoundcubeMailerRequiresSendConfirmation(): void
    {
        $mailer = $this->mailerWithResponses(
            ['status' => 'ok', 'type' => 'MAILHA', 'webmailUrl' => 'https://mail.example/roundcube/'],
            [
                $this->response(content: '<input name="_token" value="login-token">'),
                $this->response(statusCode: 302, headers: ['location' => ['/?_task=mail']]),
                $this->response(content: 'inbox'),
                $this->response(content: '<input name="_token" value="compose-token"><input name="_id" value="compose-1"><select name="_from"><option value="9" selected>Main</option></select>'),
                $this->response(content: 'failed'),
            ],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Roundcube did not confirm the message send.');
        $mailer->send('target@example.com', 'Sujet', 'Contenu');
    }

    private function clientReturning(ResponseInterface $response): HttpClientInterface
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        return $client;
    }

    /**
     * @param array{status:string, type:string, webmailUrl:string} $mailbox
     * @param list<ResponseInterface>                              $sessionResponses
     */
    private function mailerWithResponses(array $mailbox, array $sessionResponses): OvhRoundcubeMailer
    {
        $discoveryResponse = $this->createMock(ResponseInterface::class);
        $discoveryResponse->method('toArray')->with(false)->willReturn($mailbox);

        $discoveryClient = $this->createMock(HttpClientInterface::class);
        $discoveryClient->method('request')->willReturn($discoveryResponse);

        $sessionClient = $this->createMock(HttpClientInterface::class);
        $sessionClient->method('request')->willReturnOnConsecutiveCalls(...$sessionResponses);

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
