<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Port\StripeRefundClient;
use App\Shared\Application\Exception\ExternalServiceException;

final class StripeApiClient implements StripeRefundClient
{
    private const BASE_URL = 'https://api.stripe.com/v1';
    private const CONNECT_TIMEOUT_SECONDS = 5;
    private const REQUEST_TIMEOUT_SECONDS = 30;
    private const MAX_ATTEMPTS = 2;
    private const RETRYABLE_STATUS_CODES = [408, 409, 425, 429, 500, 502, 503, 504];

    public function __construct(private readonly string $secretKey)
    {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createCheckoutSession(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->request('POST', '/checkout/sessions', $payload, $idempotencyKey);
    }

    /** @return array<string, mixed> */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', '/checkout/sessions/'.rawurlencode($sessionId));
    }

    /** @return array<string, mixed> */
    public function expireCheckoutSession(string $sessionId, ?string $idempotencyKey = null): array
    {
        return $this->request('POST', '/checkout/sessions/'.rawurlencode($sessionId).'/expire', [], $idempotencyKey);
    }

    /** @return array<string, mixed> */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        return $this->request('GET', '/payment_intents/'.rawurlencode($paymentIntentId));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createRefund(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->request('POST', '/refunds', $payload, $idempotencyKey);
    }

    /**
     * @param 'GET'|'POST'         $method
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = [], ?string $idempotencyKey = null): array
    {
        if ('' === $this->secretKey) {
            throw new ExternalServiceException('Le service de paiement est momentanément indisponible.');
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_ATTEMPTS) {
            ++$attempt;

            try {
                return $this->performRequest($method, $path, $payload, $idempotencyKey, $attempt);
            } catch (ExternalServiceException $exception) {
                $lastException = $exception;

                if (!$this->shouldRetry($method, $idempotencyKey, $attempt, $exception->getCode())) {
                    throw $exception;
                }
            }
        }

        throw $lastException ?? new ExternalServiceException('Le service de paiement est momentanément indisponible.');
    }

    /**
     * @param 'GET'|'POST'         $method
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function performRequest(string $method, string $path, array $payload, ?string $idempotencyKey, int $attempt): array
    {
        $isRetry = $attempt > 1;

        $curl = curl_init();
        if (false === $curl) {
            throw new ExternalServiceException('Impossible d’initialiser le service de paiement.');
        }

        $body = http_build_query($payload);
        $url = self::BASE_URL.$path;
        if ('GET' === $method && '' !== $body) {
            $url .= '?'.$body;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_USERPWD, $this->secretKey.':');
        $headers = [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ];
        if ('GET' !== $method && null !== $idempotencyKey && '' !== trim($idempotencyKey)) {
            $headers[] = 'Idempotency-Key: '.trim($idempotencyKey);
        }
        if ($isRetry) {
            $headers[] = 'X-Hociatec-Retry-Attempt: '.$attempt;
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT_SECONDS);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT_SECONDS);

        if ('GET' !== $method) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!is_string($response)) {
            throw new ExternalServiceException(
                sprintf('Erreur cURL Stripe : %s', '' !== $error ? $error : 'réponse absente'),
                'Le service de paiement est momentanément indisponible.',
                $statusCode,
            );
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ExternalServiceException('Stripe a retourné une réponse invalide.', 'Le service de paiement a retourné une réponse inattendue.', previous: $exception);
        }

        if (!\is_array($decoded)) {
            throw new ExternalServiceException('Stripe a retourné une réponse JSON non objet.', 'Le service de paiement a retourné une réponse inattendue.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ExternalServiceException(
                sprintf('Stripe a refusé la requête avec le statut HTTP %d.', $statusCode),
                'Le service de paiement a refusé la requête.',
                $statusCode,
            );
        }

        /* @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function shouldRetry(string $method, ?string $idempotencyKey, int $attempt, int $statusCode): bool
    {
        if ($attempt >= self::MAX_ATTEMPTS) {
            return false;
        }

        $requestIsRetryable = 'GET' === $method || (null !== $idempotencyKey && '' !== trim($idempotencyKey));
        if (!$requestIsRetryable) {
            return false;
        }

        return 0 === $statusCode || in_array($statusCode, self::RETRYABLE_STATUS_CODES, true);
    }
}
