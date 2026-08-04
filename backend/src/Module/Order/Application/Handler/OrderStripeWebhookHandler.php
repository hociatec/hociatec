<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Resolver\StripeCheckoutSessionResolver;
use App\Module\Order\Application\Resolver\StripePaymentFailureResolver;
use App\Module\Order\Application\Workflow\OrderService;
use App\Module\Order\Application\Workflow\StripeApiClient;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final class OrderStripeWebhookHandler
{
    private StripePaymentFailureResolver $failureResolver;

    private StripeCheckoutSessionExpirer $sessionExpirer;

    private StripeCheckoutSessionResolver $checkoutResolver;

    private StripePaymentIntentFailedHandler $paymentIntentFailedHandler;

    public function __construct(
        private readonly OrderCheckoutSessionRepositoryPort $checkoutSessions,
        private readonly OrderRepositoryPort $orders,
        private readonly OrderService $orderCreator,
        private readonly StripeApiClient $stripe,
        private readonly DoctrineUnitOfWork $persistence,
        ?StripePaymentFailureResolver $failureResolver = null,
        ?StripeCheckoutSessionExpirer $sessionExpirer = null,
        ?StripeCheckoutSessionResolver $checkoutResolver = null,
        ?StripePaymentIntentFailedHandler $paymentIntentFailedHandler = null,
    ) {
        $this->failureResolver = $failureResolver ?? new StripePaymentFailureResolver($this->stripe);
        $this->sessionExpirer = $sessionExpirer ?? new StripeCheckoutSessionExpirer($this->stripe);
        $this->checkoutResolver = $checkoutResolver ?? new StripeCheckoutSessionResolver($this->checkoutSessions);
        $this->paymentIntentFailedHandler = $paymentIntentFailedHandler ?? new StripePaymentIntentFailedHandler($this->checkoutResolver, $this->failureResolver, $this->sessionExpirer, $this->persistence);
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
        return $this->paymentIntentFailedHandler->handle($object, $type);
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

        $this->save($checkout);

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    private function save(object $entity): void
    {
        $this->persistence->persist($entity);
        $this->persistence->commit();
    }
}
