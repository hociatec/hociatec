<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;

final class OrderStripeWebhookHandler
{
    public function __construct(
        private readonly OrderCheckoutSessionRepositoryPort $checkoutSessions,
        private readonly StripePaidCheckoutSessionHandler $paidCheckoutSessions,
        private readonly StripeExpiredCheckoutSessionHandler $expiredCheckoutSessions,
        private readonly StripePaymentIntentFailedHandler $paymentIntentFailedHandler,
    ) {
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array{type:string, sessionId:string|null}|null
     */
    public function handleCheckout(array $object, string $type): ?array
    {
        $sessionId = is_string($object['id'] ?? null) ? $object['id'] : null;
        if (null === $sessionId) {
            return null;
        }

        $checkout = $this->checkoutSessions->findOneByStripeSessionId($sessionId);
        if (!$checkout instanceof OrderCheckoutSession) {
            return null;
        }

        return match ($type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->paidCheckoutSessions->handle($checkout, $object, $type),
            'checkout.session.expired',
            'checkout.session.async_payment_failed' => $this->expiredCheckoutSessions->handle($checkout, $object, $type),
            default => ['type' => $type, 'sessionId' => $sessionId],
        };
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array{type:string, sessionId:string|null}
     */
    public function handlePaymentIntentFailed(array $object, string $type): array
    {
        return $this->paymentIntentFailedHandler->handle($object, $type);
    }
}
