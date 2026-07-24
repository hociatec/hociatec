<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

final class StripeWebhookVerifier
{
    public function __construct(
        private readonly string $webhookSecret,
        private readonly string $refundWebhookSecret,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyAndDecode(string $payload, ?string $signatureHeader): array
    {
        $secrets = array_values(array_filter(
            [$this->webhookSecret, $this->refundWebhookSecret],
            static fn (string $secret): bool => '' !== $secret,
        ));

        if ([] === $secrets) {
            throw new \RuntimeException('STRIPE_WEBHOOK_SECRET manquante.');
        }

        if (null === $signatureHeader || '' === $signatureHeader) {
            throw new \RuntimeException('Signature Stripe manquante.');
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if (null !== $key && null !== $value) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];
        if ($timestamp <= 0 || [] === $signatures) {
            throw new \RuntimeException('Signature Stripe invalide.');
        }

        if (abs(time() - $timestamp) > 300) {
            throw new \RuntimeException('Signature Stripe expirée.');
        }

        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
            foreach ($signatures as $signature) {
                if (hash_equals($expected, $signature)) {
                    /** @var array<string, mixed> $event */
                    $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

                    return $event;
                }
            }
        }

        throw new \RuntimeException('Signature Stripe invalide.');
    }
}
