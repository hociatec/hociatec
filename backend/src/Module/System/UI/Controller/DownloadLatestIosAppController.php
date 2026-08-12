<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\OutboundUrlGuard;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/public/ios/latest-download', name: 'api_public_ios_latest_download', methods: ['GET'])]
final readonly class DownloadLatestIosAppController
{
    private const LATEST_SOURCE_URL = 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json';
    private const LATEST_IPA_URL = 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-latest.ipa';

    public function __construct(
        private HttpClientInterface $httpClient,
        private AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(): Response
    {
        $release = $this->publishedRelease();
        if (null === $release) {
            return ApiResponse::error('Téléchargement iPhone indisponible.', Response::HTTP_NOT_FOUND);
        }

        try {
            OutboundUrlGuard::assertAllowedHttpUrl($release['downloadUrl']);

            $upstream = $this->httpClient->request('GET', $release['downloadUrl'], [
                'headers' => ['Accept' => 'application/octet-stream'],
                'timeout' => 60,
                'max_duration' => 120,
            ]);

            $content = $upstream->getContent();
            $contentType = $upstream->getHeaders(false)['content-type'][0] ?? 'application/octet-stream';
        } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|\InvalidArgumentException) {
            return ApiResponse::error('Téléchargement iPhone temporairement indisponible.', Response::HTTP_BAD_GATEWAY);
        }

        return $this->attachments->create($content, $release['filename'], $contentType);
    }

    /** @return array{downloadUrl: string, filename: string}|null */
    private function publishedRelease(): ?array
    {
        try {
            $payload = $this->httpClient->request('GET', self::LATEST_SOURCE_URL, [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 30,
                'max_duration' => 45,
            ])->toArray(false);
        } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface) {
            return null;
        }

        $version = trim((string) ($payload['apps'][0]['versions'][0]['version'] ?? ''));
        $buildVersion = trim((string) ($payload['apps'][0]['versions'][0]['buildVersion'] ?? ''));
        $declaredFilename = basename((string) parse_url((string) ($payload['apps'][0]['versions'][0]['downloadURL'] ?? ''), PHP_URL_PATH));
        $filename = $this->resolveFilename($declaredFilename, $version, $buildVersion);

        if ('' === trim($filename)) {
            return null;
        }

        return [
            'downloadUrl' => self::LATEST_IPA_URL,
            'filename' => $filename,
        ];
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
}
