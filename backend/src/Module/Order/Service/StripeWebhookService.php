<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Entity\RefundRequest;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Repository\RefundRequestRepository;
use App\Module\Training\Entity\TrainingEnrollment;
use App\Module\Training\Repository\TrainingEnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StripeWebhookService
{
    public function __construct(
        private readonly OrderCheckoutSessionRepository $checkoutSessions,
        private readonly RefundRequestRepository $refunds,
        private readonly OrderRepository $orderRepository,
        private readonly TrainingEnrollmentRepository $trainingEnrollments,
        private readonly OrderService $orders,
        private readonly EntityManagerInterface $em,
        private readonly StripeApiClient $stripe,
    ) {
    }

    /**
     * @return array<string, mixed>
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
            'refund.created', 'refund.updated', 'refund.failed' => $this->handleRefundEvent($object, $type),
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
            $trainingEnrollment = $this->trainingEnrollments->findOneByStripeSessionId($sessionId);
            if ($trainingEnrollment !== null) {
                return $this->handleTrainingCheckoutSession($trainingEnrollment, $object, $type);
            }

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
    private function handleTrainingCheckoutSession(TrainingEnrollment $enrollment, array $object, string $type): array
    {
        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            if (($object['payment_status'] ?? null) === 'paid') {
                $enrollment
                    ->setStatus(TrainingEnrollment::STATUS_PAID)
                    ->setPaidAt(new \DateTimeImmutable())
                    ->setStripePaymentIntentId(is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : null);
            }
        } elseif (in_array($type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $enrollment->setStatus(TrainingEnrollment::STATUS_CANCELLED);
        }

        $this->em->flush();

        return ['type' => $type, 'sessionId' => $enrollment->getStripeSessionId()];
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

        $paymentIntentId = is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : null;
        $checkout->markPaid($paymentIntentId, $paymentStatus, $type);
        $this->em->persist($checkout);
        $this->em->flush();

        if ($checkout->getOrderId() !== null) {
            $order = $this->orderRepository->find($checkout->getOrderId());
            if ($order instanceof Order && $order->getStatus() === Order::STATUS_PENDING) {
                $order->setStatus(Order::STATUS_CONFIRMED);
                $this->em->persist($order);
                $this->em->flush();
            }

            return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
        }

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
            $this->expireCheckoutSession($checkout);
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
        $this->expireCheckoutSession($checkout);
        $this->em->persist($checkout);
        $this->em->flush();

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    /**
     * @param array<string, mixed> $object
     * @return array<string, mixed>
     */
    private function handleRefundEvent(array $object, string $type): array
    {
        $stripeRefundId = is_string($object['id'] ?? null) ? $object['id'] : null;
        $refundRequestId = isset($object['metadata']['refund_request_id'])
            ? (int) $object['metadata']['refund_request_id']
            : 0;

        if ($refundRequestId <= 0) {
            return ['type' => $type, 'refundId' => $stripeRefundId, 'localRefundId' => null];
        }

        $refund = $this->refunds->find($refundRequestId);
        if (!$refund instanceof RefundRequest) {
            return ['type' => $type, 'refundId' => $stripeRefundId, 'localRefundId' => null];
        }

        if ($stripeRefundId !== null) {
            $refund->setStripeRefundId($stripeRefundId);
        }

        $stripeStatus = is_string($object['status'] ?? null) ? $object['status'] : null;
        if ($type === 'refund.failed' || in_array($stripeStatus, ['failed', 'canceled'], true)) {
            $refund->setStatus(RefundRequest::STATUS_REJECTED);
        } elseif ($stripeStatus === 'succeeded') {
            $refund->setStatus(RefundRequest::STATUS_PROCESSED);
        } elseif (in_array($stripeStatus, ['pending', 'requires_action'], true)) {
            $refund->setStatus(RefundRequest::STATUS_APPROVED);
        }

        $this->em->flush();

        return ['type' => $type, 'refundId' => $stripeRefundId, 'localRefundId' => $refund->getId()];
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

    private function expireCheckoutSession(OrderCheckoutSession $checkout): void
    {
        try {
            $this->stripe->expireCheckoutSession($checkout->getStripeSessionId());
        } catch (\Throwable) {
            // The session may already be complete or expired by the time the webhook is processed.
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyAndDecodeEvent(string $payload, ?string $signatureHeader): array
    {
        $secrets = array_values(array_filter([
            (string) ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? ''),
            (string) ($_ENV['STRIPE_REFUND_WEBHOOK_SECRET'] ?? ''),
        ], static fn (string $secret): bool => $secret !== ''));

        if ($secrets === []) {
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

        $matches = false;
        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
            foreach ($signatures as $signature) {
                if (hash_equals($expected, $signature)) {
                    $matches = true;
                    break 2;
                }
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
