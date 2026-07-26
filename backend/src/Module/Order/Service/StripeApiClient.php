<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Shared\Http\ExternalServiceException;

final class StripeApiClient
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
    public function createCheckoutSession(array $payload): array
    {
        return $this->request('POST', '/checkout/sessions', $payload);
    }

    /** @return array<string, mixed> */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', '/checkout/sessions/'.rawurlencode($sessionId));
    }

    /** @return array<string, mixed> */
    public function expireCheckoutSession(string $sessionId): array
    {
        return $this->request('POST', '/checkout/sessions/'.rawurlencode($sessionId).'/expire');
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
    public function createRefund(array $payload): array
    {
        return $this->request('POST', '/refunds', $payload);
    }

    /**
     * @param 'GET'|'POST'         $method
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
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
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ]);
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
            throw new ExternalServiceException($message);
        }

        return $decoded;
    }
}
