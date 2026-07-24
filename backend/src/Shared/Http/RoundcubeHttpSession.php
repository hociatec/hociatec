<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class RoundcubeHttpSession
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /**
     * @param array<string, string> $cookies
     * @param array<string, mixed>  $options
     * @param list<int>             $acceptedStatusCodes
     */
    public function request(
        string $method,
        string $url,
        array &$cookies,
        array $options = [],
        array $acceptedStatusCodes = [200],
    ): ResponseInterface {
        $headers = $options['headers'] ?? [];
        if ([] !== $cookies) {
            $headers['Cookie'] = $this->cookieHeader($cookies);
        }
        $options['headers'] = $headers;
        $options['max_redirects'] = 0;
        $options['timeout'] = 20;

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

    public function resolveUrl(string $baseUrl, string $location): string
    {
        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            return $location;
        }

        $parts = parse_url($baseUrl);

        return sprintf(
            '%s://%s%s',
            $parts['scheme'] ?? 'https',
            $parts['host'] ?? '',
            $location,
        );
    }

    /** @param array<string, string> $cookies */
    private function cookieHeader(array $cookies): string
    {
        $pairs = [];
        foreach ($cookies as $name => $value) {
            $pairs[] = $name.'='.$value;
        }

        return implode('; ', $pairs);
    }

    /** @param array<string, string> $cookies */
    private function storeCookies(ResponseInterface $response, array &$cookies): void
    {
        foreach ($response->getHeaders(false)['set-cookie'] ?? [] as $cookieLine) {
            $pair = trim(explode(';', $cookieLine, 2)[0]);
            if ('' === $pair || !str_contains($pair, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $pair, 2);
            if ('-del-' === $value) {
                unset($cookies[$name]);
            } else {
                $cookies[$name] = $value;
            }
        }
    }
}
