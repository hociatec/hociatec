<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

final class StripeApiClient
{
    private const BASE_URL = 'https://api.stripe.com/v1';

    public function createCheckoutSession(array $payload): array
    {
        return $this->request('POST', '/checkout/sessions', $payload);
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
    }

    public function expireCheckoutSession(string $sessionId): array
    {
        return $this->request('POST', '/checkout/sessions/' . rawurlencode($sessionId) . '/expire');
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        return $this->request('GET', '/payment_intents/' . rawurlencode($paymentIntentId));
    }

    public function createRefund(array $payload): array
    {
        return $this->request('POST', '/refunds', $payload);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $secretKey = (string) ($_ENV['STRIPE_SECRET_KEY'] ?? '');
        if ($secretKey === '') {
            throw new \RuntimeException('STRIPE_SECRET_KEY manquante.');
        }

        $curl = curl_init();
        if ($curl === false) {
            throw new \RuntimeException('Impossible d’initialiser la requête Stripe.');
        }

        $body = http_build_query($payload);
        $url = self::BASE_URL . $path;
        if ($method === 'GET' && $body !== '') {
            $url .= '?' . $body;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERPWD => $secretKey . ':',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new \RuntimeException($error !== '' ? $error : 'Appel Stripe échoué.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = (string) (($decoded['error']['message'] ?? null) ?: 'Stripe a refusé la requête.');
            throw new \RuntimeException($message);
        }

        return $decoded;
    }
}
