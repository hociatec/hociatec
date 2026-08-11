<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LibreTranslateClient
{
    private const DEFAULT_ENDPOINTS = [
        'https://translate.argosopentech.com/translate',
    ];
    private const REQUEST_TIMEOUT_SECONDS = 2.5;
    private const MAX_ATTEMPTS_PER_ENDPOINT = 2;
    private const CIRCUIT_BREAKER_FAILURE_THRESHOLD = 2;
    private const CIRCUIT_BREAKER_COOLDOWN_SECONDS = 60;
    private const ALLOWED_LANGUAGES = ['fr', 'en'];

    /** @var array<string, string> */
    private array $cache = [];
    /** @var array<string, int> */
    private array $endpointFailures = [];
    /** @var array<string, \DateTimeImmutable> */
    private array $endpointBlockedUntil = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $primaryEndpoint = null,
        private readonly ?string $fallbackEndpoint = null,
        private readonly ?string $apiKey = null,
    ) {
    }

    public function translate(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        $text = trim($text);
        if ('' === $text) {
            return '';
        }

        $sourceLanguage = strtolower(trim($sourceLanguage));
        $targetLanguage = strtolower(trim($targetLanguage));

        if ($sourceLanguage === $targetLanguage) {
            return $text;
        }

        if (!in_array($sourceLanguage, self::ALLOWED_LANGUAGES, true)
            || !in_array($targetLanguage, self::ALLOWED_LANGUAGES, true)) {
            throw new \InvalidArgumentException('Langue non supportée.');
        }

        $cacheKey = sprintf('%s|%s:%s', $sourceLanguage, $targetLanguage, md5($text));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $endpoints = $this->collectEndpoints((string) $this->primaryEndpoint, (string) $this->fallbackEndpoint);

        $translated = null;
        foreach ($endpoints as $endpoint) {
            if ($this->isCircuitOpen($endpoint)) {
                continue;
            }

            $translated = $this->requestTranslation(
                $endpoint,
                $text,
                $sourceLanguage,
                $targetLanguage,
                trim((string) $this->apiKey),
            );
            if (null !== $translated) {
                break;
            }
        }

        if (null === $translated || '' === trim($translated)) {
            return $text;
        }

        $translated = trim($translated);
        $this->cache[$cacheKey] = $translated;

        return $translated;
    }

    /** @return list<string> */
    private function collectEndpoints(string $primaryEndpoint, string $fallbackEndpoint): array
    {
        if ('' === trim($primaryEndpoint) && '' === trim($fallbackEndpoint)) {
            return self::DEFAULT_ENDPOINTS;
        }

        $rawEndpoints = array_merge(
            explode(',', $primaryEndpoint),
            explode(',', $fallbackEndpoint),
        );

        $normalized = array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            $rawEndpoints,
        ), static fn (string $value): bool => '' !== $value));

        $unique = array_values(array_unique($normalized));

        return [] !== $unique ? $unique : self::DEFAULT_ENDPOINTS;
    }

    public function translateTextOrNull(?string $text, string $sourceLanguage, string $targetLanguage): ?string
    {
        if (null === $text) {
            return null;
        }

        $translated = $this->translate($text, $sourceLanguage, $targetLanguage);

        return '' === trim($translated) ? null : trim($translated);
    }

    private function requestTranslation(
        string $endpoint,
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        string $apiKey,
    ): ?string {
        $payload = [
            'q' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text',
        ];

        if ('' !== $apiKey) {
            $payload['api_key'] = $apiKey;
        }

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS_PER_ENDPOINT; ++$attempt) {
            try {
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $payload,
                    'timeout' => self::REQUEST_TIMEOUT_SECONDS,
                    'max_duration' => self::REQUEST_TIMEOUT_SECONDS + 1.0,
                ]);

                $statusCode = $response->getStatusCode();
                if (200 !== $statusCode) {
                    $this->markFailure($endpoint);

                    if ($this->isRetryableStatusCode($statusCode) && $attempt < self::MAX_ATTEMPTS_PER_ENDPOINT) {
                        continue;
                    }

                    return null;
                }

                $this->clearFailure($endpoint);
                $data = $response->toArray(false);

                if (isset($data['translations'])
                    && is_array($data['translations'])
                    && count($data['translations']) > 0
                ) {
                    $first = $data['translations'][0];
                    if (is_array($first) && isset($first['translatedText']) && is_string($first['translatedText'])) {
                        return trim((string) $first['translatedText']);
                    }
                }

                $translatedText = null;
                if (isset($data['translatedText']) && is_string($data['translatedText'])) {
                    $translatedText = (string) $data['translatedText'];
                } elseif (isset($data['translated_text']) && is_string($data['translated_text'])) {
                    $translatedText = (string) $data['translated_text'];
                }

                return null === $translatedText ? null : trim($translatedText);
            } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface) {
                $this->markFailure($endpoint);

                if ($attempt < self::MAX_ATTEMPTS_PER_ENDPOINT) {
                    continue;
                }
            }
        }

        return null;
    }

    private function isCircuitOpen(string $endpoint): bool
    {
        $blockedUntil = $this->endpointBlockedUntil[$endpoint] ?? null;
        if (!$blockedUntil instanceof \DateTimeImmutable) {
            return false;
        }

        if ($blockedUntil > new \DateTimeImmutable()) {
            return true;
        }

        unset($this->endpointBlockedUntil[$endpoint], $this->endpointFailures[$endpoint]);

        return false;
    }

    private function markFailure(string $endpoint): void
    {
        $failures = ($this->endpointFailures[$endpoint] ?? 0) + 1;
        $this->endpointFailures[$endpoint] = $failures;

        if ($failures >= self::CIRCUIT_BREAKER_FAILURE_THRESHOLD) {
            $this->endpointBlockedUntil[$endpoint] = new \DateTimeImmutable(sprintf('+%d seconds', self::CIRCUIT_BREAKER_COOLDOWN_SECONDS));
        }
    }

    private function clearFailure(string $endpoint): void
    {
        unset($this->endpointFailures[$endpoint], $this->endpointBlockedUntil[$endpoint]);
    }

    private function isRetryableStatusCode(int $statusCode): bool
    {
        return 429 === $statusCode || $statusCode >= 500;
    }
}
