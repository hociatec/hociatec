<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Shared\Application\Exception\ExternalServiceException;
use App\Shared\Application\UnitOfWork;

final class StripeCheckoutSessionSyncService
{
    public function __construct(
        private readonly OrderCheckoutSessionRepositoryPort $checkoutSessions,
        private readonly StripeApiClient $stripe,
        private readonly UnitOfWork $persistence,
        private readonly StripeCheckoutSessionStateResolver $resolver = new StripeCheckoutSessionStateResolver(),
    ) {
    }

    public function syncPayment(OrderCheckoutSession $checkout): void
    {
        if (OrderCheckoutSession::STATUS_PAID === $checkout->getStatus()) {
            return;
        }

        try {
            $session = $this->stripe->retrieveCheckoutSession($checkout->getStripeSessionId());
        } catch (ExternalServiceException|\JsonException) {
            return;
        }

        $sessionStatus = $this->resolver->sessionStatus($session);
        if (OrderCheckoutSession::STATUS_FAILED === $checkout->getStatus()) {
            $this->expireCheckoutSession($checkout, $sessionStatus);

            return;
        }

        if ('expired' === $sessionStatus) {
            if (null !== $checkout->getFailureCode() || null !== $checkout->getFailureMessage()) {
                return;
            }

            $checkout->markExpired('checkout.session.expired');
            $this->persistence->persist($checkout);
            $this->persistence->commit();

            return;
        }

        $paymentIntentId = $this->resolver->paymentIntentId($session, $checkout->getStripePaymentIntentId());
        if (null === $paymentIntentId) {
            return;
        }

        try {
            $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);
        } catch (ExternalServiceException|\JsonException) {
            $checkout->setStripePaymentIntentId($paymentIntentId);
            $this->persistence->persist($checkout);
            $this->persistence->commit();

            return;
        }

        $paymentStatus = $this->resolver->paymentStatus($session, $paymentIntent);
        $failure = $this->resolver->failureDetails($paymentIntent);

        if (null !== $failure['code'] || null !== $failure['message']) {
            $checkout->markFailed(
                $paymentIntentId,
                $paymentStatus,
                'payment_intent.payment_failed',
                $failure['code'],
                $failure['message'],
            );
            $this->expireCheckoutSession($checkout, $sessionStatus);
        } else {
            $checkout
                ->setStripePaymentIntentId($paymentIntentId)
                ->setStripePaymentStatus($paymentStatus);
        }

        $this->persistence->persist($checkout);
        $this->persistence->commit();
    }

    public function syncRecentOpenPayments(int $limit = 20): void
    {
        foreach ($this->checkoutSessions->findRecentOpen($limit) as $item) {
            $this->syncPayment($item);
        }
    }

    private function expireCheckoutSession(OrderCheckoutSession $checkout, ?string $sessionStatus): void
    {
        if (!$this->resolver->shouldExpireRemoteSession($sessionStatus)) {
            return;
        }

        try {
            $this->stripe->expireCheckoutSession($checkout->getStripeSessionId());
        } catch (ExternalServiceException|\JsonException) {
            // The admin sync should still save the local failure even if Stripe already closed the session.
        }
    }
}
