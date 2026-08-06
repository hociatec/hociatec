<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Module\Order\Application\Resolver\StripePaymentFailureResolver;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Shared\Application\UnitOfWork;

final readonly class StripeExpiredCheckoutSessionHandler
{
    public function __construct(
        private StripePaymentFailureResolver $failureResolver,
        private StripeCheckoutSessionExpirer $sessionExpirer,
        private UnitOfWork $persistence,
    ) {
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array{type:string, sessionId:string|null}
     */
    public function handle(OrderCheckoutSession $checkout, array $object, string $type): array
    {
        if ('checkout.session.expired' === $type) {
            $checkout->markExpired($type);
        } else {
            $this->markFailed($checkout, $object, $type);
        }

        $this->persistence->persist($checkout);
        $this->persistence->commit();

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /** @param array<string, mixed> $object */
    private function markFailed(OrderCheckoutSession $checkout, array $object, string $type): void
    {
        $paymentIntentId = is_string($object['payment_intent'] ?? null)
            ? $object['payment_intent']
            : $checkout->getStripePaymentIntentId();
        $paymentStatus = is_string($object['payment_status'] ?? null)
            ? $object['payment_status']
            : 'unpaid';
        [$failureCode, $failureMessage, $livePaymentStatus] = null !== $paymentIntentId
            ? $this->failureResolver->fetch($paymentIntentId)
            : [null, null, null];

        $checkout->markFailed(
            $paymentIntentId,
            $livePaymentStatus ?? $paymentStatus,
            $type,
            $failureCode,
            $failureMessage,
        );
        $this->sessionExpirer->expire($checkout);
    }
}
