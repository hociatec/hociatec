<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Shared\Persistence\DoctrinePersistence;

final class StripeCheckoutSessionSyncService
{
    public function __construct(
        private readonly OrderCheckoutSessionRepository $checkoutSessions,
        private readonly StripeApiClient $stripe,
        private readonly DoctrinePersistence $persistence,
    ) {
    }

    public function syncPayment(OrderCheckoutSession $checkout): void
    {
        if (OrderCheckoutSession::STATUS_PAID === $checkout->getStatus()) {
            return;
        }

        try {
            $session = $this->stripe->retrieveCheckoutSession($checkout->getStripeSessionId());
        } catch (\Exception) {
            return;
        }

        $sessionStatus = is_string($session['status'] ?? null) ? $session['status'] : null;
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
            $this->persistence->flush();

            return;
        }

        $paymentIntentId = is_string($session['payment_intent'] ?? null)
            ? $session['payment_intent']
            : $checkout->getStripePaymentIntentId();

        if (null === $paymentIntentId || '' === $paymentIntentId) {
            return;
        }

        try {
            $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);
        } catch (\Exception) {
            $checkout->setStripePaymentIntentId($paymentIntentId);
            $this->persistence->persist($checkout);
            $this->persistence->flush();

            return;
        }

        $paymentStatus = is_string($paymentIntent['status'] ?? null)
            ? $paymentIntent['status']
            : (is_string($session['payment_status'] ?? null) ? $session['payment_status'] : null);
        $failureCode = $this->extractFailureCode($paymentIntent);
        $failureMessage = is_string($paymentIntent['last_payment_error']['message'] ?? null)
            ? $paymentIntent['last_payment_error']['message']
            : null;

        if (null !== $failureCode || null !== $failureMessage) {
            $checkout->markFailed(
                $paymentIntentId,
                $paymentStatus,
                'payment_intent.payment_failed',
                $failureCode,
                $failureMessage,
            );
            $this->expireCheckoutSession($checkout, $sessionStatus);
        } else {
            $checkout
                ->setStripePaymentIntentId($paymentIntentId)
                ->setStripePaymentStatus($paymentStatus);
        }

        $this->persistence->persist($checkout);
        $this->persistence->flush();
    }

    public function syncRecentOpenPayments(int $limit = 20): void
    {
        /** @var list<OrderCheckoutSession> $items */
        $items = $this->checkoutSessions->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', OrderCheckoutSession::STATUS_OPEN)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($items as $item) {
            $this->syncPayment($item);
        }
    }

    /**
     * @param array<string, mixed> $paymentIntent
     */
    private function extractFailureCode(array $paymentIntent): ?string
    {
        if (is_string($paymentIntent['last_payment_error']['decline_code'] ?? null)) {
            return $paymentIntent['last_payment_error']['decline_code'];
        }

        if (is_string($paymentIntent['last_payment_error']['code'] ?? null)) {
            return $paymentIntent['last_payment_error']['code'];
        }

        return null;
    }

    private function expireCheckoutSession(OrderCheckoutSession $checkout, ?string $sessionStatus): void
    {
        if ('open' !== $sessionStatus) {
            return;
        }

        try {
            $this->stripe->expireCheckoutSession($checkout->getStripeSessionId());
        } catch (\Exception) {
            // The admin sync should still save the local failure even if Stripe already closed the session.
        }
    }
}
