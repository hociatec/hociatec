<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LibreTranslateClient
{
    private const DEFAULT_ENDPOINTS = [
        'https://translate.argosopentech.com/translate',
    ];
    private const REQUEST_TIMEOUT_SECONDS = 2.5;
    private const ALLOWED_LANGUAGES = ['fr', 'en'];

    /** @var array<string, string> */
    private array $cache = [];

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
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

        $primaryEndpoint = $this->readEnv('LIBRETRANSLATE_ENDPOINT');
        $fallbackEndpoint = $this->readEnv('LIBRETRANSLATE_FALLBACK_ENDPOINT');
        $apiKey = $this->readEnv('LIBRETRANSLATE_API_KEY');

        $endpoints = $this->collectEndpoints($primaryEndpoint, $fallbackEndpoint);

        $translated = null;
        foreach ($endpoints as $endpoint) {
            $translated = $this->requestTranslation($endpoint, $text, $sourceLanguage, $targetLanguage, $apiKey);
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

    private function readEnv(string $name): string
    {
        if (isset($_ENV[$name]) && is_string($_ENV[$name])) {
            return trim($_ENV[$name]);
        }

        $serverValue = $_SERVER[$name] ?? null;
        if (is_string($serverValue)) {
            return trim($serverValue);
        }

        $envValue = getenv($name);
        return is_string($envValue) ? trim($envValue) : '';
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

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $payload,
                'timeout' => self::REQUEST_TIMEOUT_SECONDS,
                'max_duration' => self::REQUEST_TIMEOUT_SECONDS + 1.0,
            ]);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $data = $response->toArray(false);
            if (!is_array($data)) {
                return null;
            }

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
        } catch (TransportExceptionInterface | \Throwable) {
            return null;
        }
    }
}
