<?php

declare(strict_types=1);

namespace App\Module\System\Application\Provider;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class LatestIosAppDownloadProvider
{
    private const LATEST_IPA_URL = 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-latest.ipa';

    public function __construct(
        private LatestIosAltStoreSourceProvider $source,
        private HttpClientInterface $httpClient,
    ) {
    }

    /** @return array{content:string,contentType:string,filename:string}|null */
    public function fetchLatestDownload(): ?array
    {
        $release = $this->publishedRelease();
        if (null === $release || !$this->isAllowedHttpUrl($release['downloadUrl'])) {
            return null;
        }

        try {
            $upstream = $this->httpClient->request('GET', $release['downloadUrl'], [
                'headers' => ['Accept' => 'application/octet-stream'],
                'timeout' => 60,
                'max_duration' => 120,
            ]);
        } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface) {
            return null;
        }

        return [
            'content' => $upstream->getContent(),
            'contentType' => $upstream->getHeaders(false)['content-type'][0] ?? 'application/octet-stream',
            'filename' => $release['filename'],
        ];
    }

    /** @return array{downloadUrl:string,filename:string}|null */
    private function publishedRelease(): ?array
    {
        $payload = $this->source->fetchPayload();
        if (!is_array($payload)) {
            return null;
        }

        $version = trim((string) ($payload['apps'][0]['versions'][0]['version'] ?? ''));
        $buildVersion = trim((string) ($payload['apps'][0]['versions'][0]['buildVersion'] ?? ''));
        $declaredFilename = basename((string) parse_url((string) ($payload['apps'][0]['versions'][0]['downloadURL'] ?? ''), PHP_URL_PATH));
        $filename = $this->resolveFilename($declaredFilename, $version, $buildVersion);
        if ('' === trim($filename)) {
            return null;
        }

        return ['downloadUrl' => self::LATEST_IPA_URL, 'filename' => $filename];
    }

    private function resolveFilename(string $declaredFilename, string $version, string $buildVersion): string
    {
        $declaredFilename = trim($declaredFilename);
        if ('' !== $declaredFilename && str_ends_with(strtolower($declaredFilename), '.ipa')) {
            return $declaredFilename;
        }

        if ('' !== $version && '' !== $buildVersion) {
            return sprintf('hociatec-altstore-v%s-b%s.ipa', $version, $buildVersion);
        }

        return 'hociatec-altstore-latest.ipa';
    }

    private function isAllowedHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return 'https' === $scheme && in_array($host, ['github.com', 'objects.githubusercontent.com'], true);
    }
}
