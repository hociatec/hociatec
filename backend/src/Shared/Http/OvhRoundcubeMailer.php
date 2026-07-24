<?php

declare(strict_types=1);

namespace App\Shared\Http;

final readonly class OvhRoundcubeMailer
{
    public function __construct(
        private RoundcubeCredentialsProvider $credentials,
        private OvhMailboxDiscovery $discovery,
        private RoundcubeHttpSession $http,
        private RoundcubeFormParser $forms,
    ) {
    }

    public function supportsConfiguredMailbox(): bool
    {
        return null !== $this->credentials->provide();
    }

    public function send(string $to, string $subject, string $message, ?string $replyTo = null): void
    {
        $credentials = $this->credentials->provide();
        if (null === $credentials) {
            throw new \RuntimeException('OVH mailbox credentials are not configured.');
        }

        $mailbox = $this->discovery->discover($credentials['email']);
        $webmailUrl = $mailbox['webmailUrl'] ?? null;
        if (!is_string($webmailUrl) || '' === $webmailUrl || !str_contains($webmailUrl, 'roundcube')) {
            throw new \RuntimeException('Unsupported OVH webmail endpoint.');
        }
        if (($mailbox['type'] ?? null) !== 'MAILHA') {
            throw new \RuntimeException('Unsupported OVH mailbox type.');
        }

        $cookies = [];
        $this->login($webmailUrl, $credentials, $cookies);
        [$token, $composeId, $identityId] = $this->prepareComposeForm($webmailUrl, $cookies);
        $response = $this->http->request('POST', $webmailUrl.'?_task=mail&_lang=fr_FR&_framed=1', $cookies, [
            'body' => http_build_query([
                '_token' => $token,
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
                'Referer' => $webmailUrl.'?_task=mail&_action=compose',
                'X-Roundcube-Request' => $token,
            ],
        ]);

        if (!str_contains($response->getContent(), 'sent_successfully')) {
            throw new \RuntimeException('Roundcube did not confirm the message send.');
        }
    }

    /**
     * @param array{email:string, password:string} $credentials
     * @param array<string, string>                $cookies
     */
    private function login(string $webmailUrl, array $credentials, array &$cookies): void
    {
        $loginHtml = $this->http->request('GET', $webmailUrl.'?_task=login', $cookies)->getContent();
        $loginToken = $this->forms->inputValue($loginHtml, '_token');
        if (null === $loginToken) {
            throw new \RuntimeException('Unable to read Roundcube login token.');
        }

        $response = $this->http->request('POST', $webmailUrl.'?_task=login', $cookies, [
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
                'Referer' => $webmailUrl.'?_task=login',
            ],
        ], [200, 302]);

        $location = $response->getHeaders(false)['location'][0] ?? null;
        if (is_string($location) && str_contains($location, '_task=mail')) {
            $this->http->request('GET', $this->http->resolveUrl($webmailUrl, $location), $cookies);

            return;
        }

        $html = $response->getContent();
        if (str_contains($html, '_task=login') && str_contains($html, '_action=login')) {
            throw new \RuntimeException('Roundcube login failed.');
        }
    }

    /**
     * @param array<string, string> $cookies
     *
     * @return array{string, string, string}
     */
    private function prepareComposeForm(string $webmailUrl, array &$cookies): array
    {
        $response = $this->http->request(
            'GET',
            $webmailUrl.'?_task=mail&_action=compose',
            $cookies,
            [],
            [200, 302],
        );
        $location = $response->getHeaders(false)['location'][0] ?? null;
        if (is_string($location) && '' !== $location) {
            $response = $this->http->request(
                'GET',
                $this->http->resolveUrl($webmailUrl, $location),
                $cookies,
            );
        }

        $html = $response->getContent();
        $token = $this->forms->inputValue($html, '_token');
        $composeId = $this->forms->inputValue($html, '_id');
        $identityId = $this->forms->selectedOptionValue($html, '_from');
        if (null === $token || null === $composeId || null === $identityId) {
            throw new \RuntimeException('Unable to prepare Roundcube compose form.');
        }

        return [$token, $composeId, $identityId];
    }
}
