<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Port\StripeRefundClient;
use App\Shared\Application\Exception\ExternalServiceException;

final class StripeApiClient implements StripeRefundClient
{
    private const BASE_URL = 'https://api.stripe.com/v1';

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

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        if ('GET' !== $method) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!is_string($response)) {
            throw new ExternalServiceException('Appel du service de paiement échoué.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = (string) (($decoded['error']['message'] ?? null) ?: 'Stripe a refusé la requête.');
            throw new ExternalServiceException($message, 'Le service de paiement a refusé la requête.');
        }

        return $decoded;
    }
}
