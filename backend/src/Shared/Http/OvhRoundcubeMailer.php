<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OvhRoundcubeMailer
{
    private const DISCOVERY_URL = 'https://msservices.eu.ovhapis.com/1.0/webmail/';

    private readonly HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create([
            'timeout' => 20,
            'max_redirects' => 0,
        ]);
    }

    public function supportsConfiguredMailbox(): bool
    {
        return $this->resolveCredentials() !== null;
    }

    public function send(string $to, string $subject, string $message, ?string $replyTo = null): void
    {
        $credentials = $this->resolveCredentials();
        if ($credentials === null) {
            throw new \RuntimeException('OVH mailbox credentials are not configured.');
        }

        $discovery = $this->discoverMailbox($credentials['email']);
        $webmailUrl = $discovery['webmailUrl'] ?? null;
        $type = $discovery['type'] ?? null;

        if (!is_string($webmailUrl) || $webmailUrl === '' || !str_contains($webmailUrl, 'roundcube')) {
            throw new \RuntimeException('Unsupported OVH webmail endpoint.');
        }

        if (!is_string($type) || $type !== 'MAILHA') {
            throw new \RuntimeException('Unsupported OVH mailbox type.');
        }

        $cookies = [];
        $loginPage = $this->request('GET', $webmailUrl . '?_task=login', $cookies);
        $loginHtml = $loginPage->getContent();
        $loginToken = $this->extractInputValue($loginHtml, '_token');

        if ($loginToken === null) {
            throw new \RuntimeException('Unable to read Roundcube login token.');
        }

        $loginResponse = $this->request('POST', $webmailUrl . '?_task=login', $cookies, [
            'body' => http_build_query([
                '_token' => $loginToken,
                '_task' => 'login',
                '_action' => 'login',
                '_timezone' => 'Europe/Paris',
                '_url' => '',
                '_user' => $credentials['email'],
                '_pass' => $credentials['password'],
            ], '', '&', \PHP_QUERY_RFC3986),
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer' => $webmailUrl . '?_task=login',
                ],
            ],
            [200, 302]
        );

        $location = $loginResponse->getHeaders(false)['location'][0] ?? null;
        if (is_string($location) && str_contains($location, '_task=mail')) {
            $this->request('GET', $this->resolveUrl($webmailUrl, $location), $cookies, [], [200]);
        } else {
            $loginResponseHtml = $loginResponse->getContent();
            if (
                str_contains($loginResponseHtml, '_task=login')
                && str_contains($loginResponseHtml, '_action=login')
            ) {
                throw new \RuntimeException('Roundcube login failed.');
            }
        }

        $composeEntryUrl = $webmailUrl . '?_task=mail&_action=compose';
        $composeRedirect = $this->request('GET', $composeEntryUrl, $cookies, [], [200, 302]);
        $composeLocation = $composeRedirect->getHeaders(false)['location'][0] ?? null;
        if (is_string($composeLocation) && $composeLocation !== '') {
            $composePage = $this->request('GET', $this->resolveUrl($webmailUrl, $composeLocation), $cookies);
        } else {
            $composePage = $composeRedirect;
        }
        $composeHtml = $composePage->getContent();
        $composeToken = $this->extractInputValue($composeHtml, '_token');
        $composeId = $this->extractInputValue($composeHtml, '_id');
        $identityId = $this->extractSelectedOptionValue($composeHtml, '_from');

        if ($composeToken === null || $composeId === null || $identityId === null) {
            throw new \RuntimeException('Unable to prepare Roundcube compose form.');
        }

        $sendResponse = $this->request('POST', $webmailUrl . '?_task=mail&_lang=fr_FR&_framed=1', $cookies, [
            'body' => http_build_query([
                '_token' => $composeToken,
                '_task' => 'mail',
                '_action' => 'send',
                '_id' => $composeId,
                '_attachments' => '',
                '_from' => $identityId,
                '_to' => $to,
                '_cc' => '',
                '_bcc' => '',
                '_replyto' => $replyTo ?? '',
                '_followupto' => '',
                '_subject' => $subject,
                '_draft_saveid' => '',
                '_draft' => '',
                '_is_html' => '0',
                '_framed' => '1',
                '_message' => $message,
            ], '', '&', \PHP_QUERY_RFC3986),
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer' => $webmailUrl . '?_task=mail&_action=compose',
                'X-Roundcube-Request' => $composeToken,
            ],
        ]);

        $sendHtml = $sendResponse->getContent();
        if (!str_contains($sendHtml, 'sent_successfully')) {
            throw new \RuntimeException('Roundcube did not confirm the message send.');
        }
    }

    /**
     * @return array{email: string, password: string}|null
     */
    private function resolveCredentials(): ?array
    {
        $ovhEmail = $_ENV['OVH_WEBMAIL_EMAIL'] ?? $_SERVER['OVH_WEBMAIL_EMAIL'] ?? null;
        $ovhPassword = $_ENV['OVH_WEBMAIL_PASSWORD'] ?? $_SERVER['OVH_WEBMAIL_PASSWORD'] ?? null;

        if (is_string($ovhEmail) && $ovhEmail !== '' && is_string($ovhPassword) && $ovhPassword !== '') {
            return [
                'email' => $ovhEmail,
                'password' => $ovhPassword,
            ];
        }

        $dsn = $_ENV['MAILER_DSN'] ?? $_SERVER['MAILER_DSN'] ?? null;
        if (!is_string($dsn) || $dsn === '') {
            return null;
        }

        $parts = parse_url($dsn);
        if (!is_array($parts)) {
            return null;
        }

        $user = isset($parts['user']) ? rawurldecode((string) $parts['user']) : null;
        $pass = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : null;

        if (!is_string($user) || $user === '' || !is_string($pass) || $pass === '') {
            return null;
        }

        return [
            'email' => $user,
            'password' => $pass,
        ];
    }

    /**
     * @return array{status?: mixed, type?: mixed, webmailUrl?: mixed}
     */
    private function discoverMailbox(string $email): array
    {
        $response = $this->httpClient->request('GET', self::DISCOVERY_URL, [
            'query' => ['email' => $email],
        ]);

        /** @var array{status?: mixed, type?: mixed, webmailUrl?: mixed} $data */
        $data = $response->toArray(false);
        if (($data['status'] ?? null) !== 'ok') {
            throw new \RuntimeException('OVH mailbox discovery failed.');
        }

        return $data;
    }

    /**
     * @param array<string, string> $cookies
     * @param array<string, mixed> $options
     * @param list<int> $acceptedStatusCodes
     */
    private function request(string $method, string $url, array &$cookies, array $options = [], array $acceptedStatusCodes = [200]): ResponseInterface
    {
        $headers = $options['headers'] ?? [];
        if ($cookies !== []) {
            $headers['Cookie'] = $this->buildCookieHeader($cookies);
        }

        $options['headers'] = $headers;

        $response = $this->httpClient->request($method, $url, $options);
        $this->storeCookies($response, $cookies);

        try {
            $statusCode = $response->getStatusCode();
            if (!in_array($statusCode, $acceptedStatusCodes, true)) {
                throw new \RuntimeException(sprintf('Unexpected HTTP status %d for %s %s.', $statusCode, $method, $url));
            }

            return $response;
        } catch (ExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('HTTP request failed for %s %s.', $method, $url), 0, $exception);
        }
    }

    /**
     * @param array<string, string> $cookies
     */
    private function buildCookieHeader(array $cookies): string
    {
        $pairs = [];
        foreach ($cookies as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }

        return implode('; ', $pairs);
    }

    /**
     * @param array<string, string> $cookies
     */
    private function storeCookies(ResponseInterface $response, array &$cookies): void
    {
        foreach ($response->getHeaders(false)['set-cookie'] ?? [] as $cookieLine) {
            $pair = trim(explode(';', $cookieLine, 2)[0] ?? '');
            if ($pair === '' || !str_contains($pair, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $pair, 2);
            if ($value === '-del-') {
                unset($cookies[$name]);
                continue;
            }

            $cookies[$name] = $value;
        }
    }

    private function extractInputValue(string $html, string $name): ?string
    {
        $xpath = $this->createXpath($html);
        $node = $xpath->query(sprintf('//input[@name="%s"]', $name))->item(0);
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $value = $node->getAttribute('value');

        return $value !== '' ? $value : null;
    }

    private function extractSelectedOptionValue(string $html, string $selectName): ?string
    {
        $xpath = $this->createXpath($html);
        $nodeList = $xpath->query(sprintf('//select[@name="%s"]/option[@selected]', $selectName));
        $node = $nodeList->item(0);

        if (!$node instanceof \DOMElement) {
            $node = $xpath->query(sprintf('//select[@name="%s"]/option', $selectName))->item(0);
        }

        if (!$node instanceof \DOMElement) {
            return null;
        }

        $value = $node->getAttribute('value');

        return $value !== '' ? $value : null;
    }

    private function createXpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        return new \DOMXPath($dom);
    }

    private function resolveUrl(string $baseUrl, string $location): string
    {
        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            return $location;
        }

        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        return sprintf('%s://%s%s', $scheme, $host, $location);
    }
}
