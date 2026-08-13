<?php

declare(strict_types=1);

namespace App\Module\System\Application\Provider;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class LatestIosAltStoreSourceProvider
{
    public const SOURCE_URL = 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json';

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function fetchContent(): ?string
    {
        try {
            return $this->httpClient->request('GET', self::SOURCE_URL, [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 30,
                'max_duration' => 45,
            ])->getContent();
        } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface) {
            return null;
        }
    }

    /** @return array<string, mixed>|null */
    public function fetchPayload(): ?array
    {
        try {
            $payload = $this->httpClient->request('GET', self::SOURCE_URL, [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 30,
                'max_duration' => 45,
            ])->toArray(false);
        } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface) {
            return null;
        }

        return $payload;
    }
}
