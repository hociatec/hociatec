<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OvhMailboxDiscovery
{
    private const URL = 'https://msservices.eu.ovhapis.com/1.0/webmail/';

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /** @return array{status?:mixed, type?:mixed, webmailUrl?:mixed} */
    public function discover(string $email): array
    {
        $data = $this->httpClient->request('GET', self::URL, [
            'query' => ['email' => $email],
        ])->toArray(false);

        if (($data['status'] ?? null) !== 'ok') {
            throw new \RuntimeException('OVH mailbox discovery failed.');
        }

        return $data;
    }
}
