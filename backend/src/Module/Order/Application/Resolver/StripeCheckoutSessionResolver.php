<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Resolver;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;

final readonly class StripeCheckoutSessionResolver
{
    public function __construct(private OrderCheckoutSessionRepositoryPort $checkoutSessions)
    {
    }

    /**
     * @param array<string, mixed> $paymentIntent
     */
    public function fromPaymentIntentFailure(string $paymentIntentId, array $paymentIntent): ?OrderCheckoutSession
    {
        $checkout = $this->checkoutSessions->findOneByStripePaymentIntentId($paymentIntentId);
        if ($checkout instanceof OrderCheckoutSession) {
            return $checkout;
        }

        $localToken = is_string($paymentIntent['metadata']['local_checkout_token'] ?? null)
            ? $paymentIntent['metadata']['local_checkout_token']
            : null;

        return null !== $localToken ? $this->checkoutSessions->findOneByToken($localToken) : null;
    }
}
