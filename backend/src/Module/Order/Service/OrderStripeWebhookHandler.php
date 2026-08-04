<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Repository\OrderRepository;
use App\Shared\Http\ExternalServiceException;
use App\Shared\Persistence\DoctrinePersistence;

final class OrderStripeWebhookHandler
{
    public function __construct(
        private readonly OrderCheckoutSessionRepository $checkoutSessions,
        private readonly OrderRepository $orders,
        private readonly OrderService $orderCreator,
        private readonly StripeApiClient $stripe,
        private readonly DoctrinePersistence $persistence,
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
            'checkout.session.async_payment_succeeded' => $this->handlePaidSession($checkout, $object, $type),
            'checkout.session.expired',
            'checkout.session.async_payment_failed' => $this->handleExpiredOrFailedSession($checkout, $object, $type),
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
        $paymentIntentId = is_string($object['id'] ?? null) ? $object['id'] : null;
        if (null === $paymentIntentId) {
            throw new \RuntimeException('PaymentIntent Stripe introuvable.');
        }

        $checkout = $this->checkoutSessions->findOneByStripePaymentIntentId($paymentIntentId);
        if (null === $checkout) {
            $localToken = is_string($object['metadata']['local_checkout_token'] ?? null)
                ? $object['metadata']['local_checkout_token']
                : null;
            $checkout = null !== $localToken
                ? $this->checkoutSessions->findOneByToken($localToken)
                : null;
        }

        if (!$checkout instanceof OrderCheckoutSession) {
            return ['type' => $type, 'sessionId' => null];
        }

        $paymentStatus = is_string($object['status'] ?? null)
            ? $object['status']
            : 'requires_payment_method';
        $failureCode = is_string($object['last_payment_error']['decline_code'] ?? null)
            ? $object['last_payment_error']['decline_code']
            : (is_string($object['last_payment_error']['code'] ?? null)
                ? $object['last_payment_error']['code']
                : null);
        $failureMessage = is_string($object['last_payment_error']['message'] ?? null)
            ? $object['last_payment_error']['message']
            : null;

        $checkout->markFailed($paymentIntentId, $paymentStatus, $type, $failureCode, $failureMessage);
        $this->expireCheckoutSession($checkout);
        $this->save($checkout);

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array{type:string, sessionId:string|null}
     */
    private function handlePaidSession(OrderCheckoutSession $checkout, array $object, string $type): array
    {
        $paymentStatus = (string) ($object['payment_status'] ?? '');
        if ('paid' !== $paymentStatus) {
            return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
        }

        $paymentIntentId = is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : null;
        $checkout->markPaid($paymentIntentId, $paymentStatus, $type);
        $this->save($checkout);

        if (null !== $checkout->getOrderId()) {
            $order = $this->orders->find($checkout->getOrderId());
            if ($order instanceof Order && Order::STATUS_PENDING === $order->getStatus()) {
                $order->setStatus(Order::STATUS_CONFIRMED);
                $this->save($order);
            }

            return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
        }

        $order = $this->orderCreator->createFromCheckoutSession($checkout);
        $checkout->setOrderId($order->getId());
        $this->save($checkout);

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array{type:string, sessionId:string|null}
     */
    private function handleExpiredOrFailedSession(
        OrderCheckoutSession $checkout,
        array $object,
        string $type,
    ): array {
        if ('checkout.session.expired' === $type) {
            $checkout->markExpired($type);
        } else {
            $paymentIntentId = is_string($object['payment_intent'] ?? null)
                ? $object['payment_intent']
                : $checkout->getStripePaymentIntentId();
            $paymentStatus = is_string($object['payment_status'] ?? null)
                ? $object['payment_status']
                : 'unpaid';
            [$failureCode, $failureMessage, $livePaymentStatus] = null !== $paymentIntentId
                ? $this->fetchPaymentIntentFailure($paymentIntentId)
                : [null, null, null];

            $checkout->markFailed(
                $paymentIntentId,
                $livePaymentStatus ?? $paymentStatus,
                $type,
                $failureCode,
                $failureMessage,
            );
            $this->expireCheckoutSession($checkout);
        }

        $this->save($checkout);

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /**
     * @return array{0:string|null,1:string|null,2:string|null}
     */
    private function fetchPaymentIntentFailure(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);
        } catch (ExternalServiceException|\JsonException) {
            return [null, null, null];
        }

        $paymentStatus = is_string($paymentIntent['status'] ?? null) ? $paymentIntent['status'] : null;
        $failureCode = is_string($paymentIntent['last_payment_error']['decline_code'] ?? null)
            ? $paymentIntent['last_payment_error']['decline_code']
            : (is_string($paymentIntent['last_payment_error']['code'] ?? null)
                ? $paymentIntent['last_payment_error']['code']
                : null);
        $failureMessage = is_string($paymentIntent['last_payment_error']['message'] ?? null)
            ? $paymentIntent['last_payment_error']['message']
            : null;

        return [$failureCode, $failureMessage, $paymentStatus];
    }

    private function expireCheckoutSession(OrderCheckoutSession $checkout): void
    {
        try {
            $this->stripe->expireCheckoutSession($checkout->getStripeSessionId());
        } catch (ExternalServiceException|\JsonException) {
            // Stripe may already have completed or expired the session.
        }
    }

    private function save(object $entity): void
    {
        $this->persistence->persist($entity);
        $this->persistence->flush();
    }
}
