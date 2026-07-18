<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StripeWebhookService
{
    public function __construct(
        private readonly OrderCheckoutSessionRepository $checkoutSessions,
        private readonly OrderService $orders,
        private readonly EntityManagerInterface $em,
        private readonly StripeApiClient $stripe,
    ) {
    }

    /**
     * @return array{type:string, sessionId:string|null}
     */
    public function handle(string $payload, ?string $signatureHeader): array
    {
        $event = $this->verifyAndDecodeEvent($payload, $signatureHeader);
        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? null;

        if (!is_array($object)) {
            throw new \RuntimeException('Webhook Stripe invalide.');
        }

        return match ($type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.expired', 'checkout.session.async_payment_failed' => $this->handleCheckoutSessionEvent($object, $type),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($object, $type),
            default => ['type' => $type, 'sessionId' => is_string($object['id'] ?? null) ? $object['id'] : null],
        };
    }

    /**
     * @param array<string, mixed> $object
     * @return array{type:string, sessionId:string|null}
     */
    private function handleCheckoutSessionEvent(array $object, string $type): array
    {
        $sessionId = is_string($object['id'] ?? null) ? $object['id'] : null;
        if ($sessionId === null) {
            throw new \RuntimeException('Session Stripe introuvable.');
        }

        $checkout = $this->checkoutSessions->findOneByStripeSessionId($sessionId);
        if ($checkout === null) {
            return ['type' => $type, 'sessionId' => $sessionId];
        }

        return match ($type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->handlePaidSession($checkout, $object, $type),
            'checkout.session.expired', 'checkout.session.async_payment_failed' => $this->handleExpiredOrFailedSession($checkout, $object, $type),
            default => ['type' => $type, 'sessionId' => $sessionId],
        };
    }

    /**
     * @param array<string, mixed> $object
     * @return array{type:string, sessionId:string|null}
     */
    private function handlePaidSession(OrderCheckoutSession $checkout, array $object, string $type): array
    {
        $paymentStatus = (string) ($object['payment_status'] ?? '');
        if ($paymentStatus !== 'paid') {
            return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
        }

        if ($checkout->getOrderId() !== null) {
            return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
        }

        $paymentIntentId = is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : null;
        $checkout->markPaid($paymentIntentId, $paymentStatus, $type);
        $this->em->persist($checkout);
        $this->em->flush();

        $order = $this->orders->createFromCheckoutSession($checkout);
        $checkout->setOrderId($order->getId());
        $this->em->persist($checkout);
        $this->em->flush();

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /**
     * @return array{type:string, sessionId:string|null}
     */
    private function handleExpiredOrFailedSession(OrderCheckoutSession $checkout, array $object, string $type): array
    {
        if ($type === 'checkout.session.expired') {
            $checkout->markExpired($type);
        } else {
            $paymentIntentId = is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : $checkout->getStripePaymentIntentId();
            $paymentStatus = is_string($object['payment_status'] ?? null) ? $object['payment_status'] : 'unpaid';
            [$failureCode, $failureMessage, $livePaymentStatus] = $paymentIntentId !== null
                ? $this->fetchPaymentIntentFailure($paymentIntentId)
                : [null, null, null];

            $checkout->markFailed(
                $paymentIntentId,
                $livePaymentStatus ?? $paymentStatus,
                $type,
                $failureCode,
                $failureMessage,
            );
        }

        $this->em->persist($checkout);
        $this->em->flush();

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /**
     * @param array<string, mixed> $object
     * @return array{type:string, sessionId:string|null}
     */
    private function handlePaymentIntentFailed(array $object, string $type): array
    {
        $paymentIntentId = is_string($object['id'] ?? null) ? $object['id'] : null;
        if ($paymentIntentId === null) {
            throw new \RuntimeException('PaymentIntent Stripe introuvable.');
        }

        $checkout = $this->checkoutSessions->findOneByStripePaymentIntentId($paymentIntentId);
        if ($checkout === null) {
            $localToken = is_string($object['metadata']['local_checkout_token'] ?? null) ? $object['metadata']['local_checkout_token'] : null;
            if ($localToken !== null) {
                $checkout = $this->checkoutSessions->findOneByToken($localToken);
            }
        }

        if ($checkout === null) {
            return ['type' => $type, 'sessionId' => null];
        }

        $paymentStatus = is_string($object['status'] ?? null) ? $object['status'] : 'requires_payment_method';
        $failureCode = is_string($object['last_payment_error']['decline_code'] ?? null)
            ? $object['last_payment_error']['decline_code']
            : (is_string($object['last_payment_error']['code'] ?? null) ? $object['last_payment_error']['code'] : null);
        $failureMessage = is_string($object['last_payment_error']['message'] ?? null)
            ? $object['last_payment_error']['message']
            : null;

        $checkout->markFailed($paymentIntentId, $paymentStatus, $type, $failureCode, $failureMessage);
        $this->em->persist($checkout);
        $this->em->flush();

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /**
     * @return array{0:string|null,1:string|null,2:string|null}
     */
    private function fetchPaymentIntentFailure(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);
        } catch (\Throwable) {
            return [null, null, null];
        }

        $paymentStatus = is_string($paymentIntent['status'] ?? null) ? $paymentIntent['status'] : null;
        $failureCode = is_string($paymentIntent['last_payment_error']['decline_code'] ?? null)
            ? $paymentIntent['last_payment_error']['decline_code']
            : (is_string($paymentIntent['last_payment_error']['code'] ?? null) ? $paymentIntent['last_payment_error']['code'] : null);
        $failureMessage = is_string($paymentIntent['last_payment_error']['message'] ?? null)
            ? $paymentIntent['last_payment_error']['message']
            : null;

        return [$failureCode, $failureMessage, $paymentStatus];
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyAndDecodeEvent(string $payload, ?string $signatureHeader): array
    {
        $secret = (string) ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');
        if ($secret === '') {
            throw new \RuntimeException('STRIPE_WEBHOOK_SECRET manquante.');
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            throw new \RuntimeException('Signature Stripe manquante.');
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];
        if ($timestamp <= 0 || $signatures === []) {
            throw new \RuntimeException('Signature Stripe invalide.');
        }

        if (abs(time() - $timestamp) > 300) {
            throw new \RuntimeException('Signature Stripe expirée.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $matches = false;
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $matches = true;
                break;
            }
        }

        if (!$matches) {
            throw new \RuntimeException('Signature Stripe invalide.');
        }

        /** @var array<string, mixed> $event */
        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return $event;
    }
}
