<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

final class StripeWebhookService
{
    public function __construct(
        private readonly StripeWebhookVerifier $verifier,
        private readonly OrderStripeWebhookHandler $orders,
        private readonly TrainingStripeWebhookHandler $training,
        private readonly RefundStripeWebhookHandler $refunds,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(string $payload, ?string $signatureHeader): array
    {
        $event = $this->verifier->verifyAndDecode($payload, $signatureHeader);
        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? null;

        if (!is_array($object)) {
            throw new \RuntimeException('Webhook Stripe invalide.');
        }

        if (in_array($type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.expired',
            'checkout.session.async_payment_failed',
        ], true)) {
            return $this->handleCheckout($object, $type);
        }

        return match ($type) {
            'payment_intent.payment_failed' => $this->orders->handlePaymentIntentFailed($object, $type),
            'refund.created', 'refund.updated', 'refund.failed' => $this->refunds->handle($object, $type),
            default => [
                'type' => $type,
                'sessionId' => is_string($object['id'] ?? null) ? $object['id'] : null,
            ],
        };
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array<string, mixed>
     */
    private function handleCheckout(array $object, string $type): array
    {
        $sessionId = is_string($object['id'] ?? null) ? $object['id'] : null;
        if (null === $sessionId) {
            throw new \RuntimeException('Session Stripe introuvable.');
        }

        return $this->orders->handleCheckout($object, $type)
            ?? $this->training->handleCheckout($object, $type)
            ?? ['type' => $type, 'sessionId' => $sessionId];
    }
}
