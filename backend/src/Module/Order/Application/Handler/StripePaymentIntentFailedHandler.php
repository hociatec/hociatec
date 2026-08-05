<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Module\Order\Application\Resolver\StripeCheckoutSessionResolver;
use App\Module\Order\Application\Resolver\StripePaymentFailureResolver;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Shared\Application\UnitOfWork;

final readonly class StripePaymentIntentFailedHandler
{
    public function __construct(
        private StripeCheckoutSessionResolver $checkoutResolver,
        private StripePaymentFailureResolver $failureResolver,
        private StripeCheckoutSessionExpirer $sessionExpirer,
        private UnitOfWork $persistence,
    ) {
    }

    /**
     * @param array<string, mixed> $paymentIntent
     *
     * @return array{type:string, sessionId:string|null}
     */
    public function handle(array $paymentIntent, string $type): array
    {
        $paymentIntentId = is_string($paymentIntent['id'] ?? null) ? $paymentIntent['id'] : null;
        if (null === $paymentIntentId) {
            throw new \RuntimeException('PaymentIntent Stripe introuvable.');
        }

        $checkout = $this->checkoutResolver->fromPaymentIntentFailure($paymentIntentId, $paymentIntent);
        if (!$checkout instanceof OrderCheckoutSession) {
            return ['type' => $type, 'sessionId' => null];
        }

        [$failureCode, $failureMessage, $paymentStatus] = $this->failureResolver->fromPayload($paymentIntent);
        $checkout->markFailed($paymentIntentId, $paymentStatus ?? 'requires_payment_method', $type, $failureCode, $failureMessage);
        $this->sessionExpirer->expire($checkout);
        $this->persistence->persist($checkout);
        $this->persistence->commit();

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }
}
