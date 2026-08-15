<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Handler\StripeCheckoutSessionExpirer;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class CustomerCheckoutSessionPortalService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OrderAccessPolicy $accessPolicy,
        private OrderFormatter $formatter,
        private StripeCheckoutSessionSyncService $checkoutSync,
        private StripeCheckoutSessionExpirer $checkoutExpirer,
        private UnitOfWork $persistence,
        private OrderCheckoutSessionRepositoryPort $checkoutSessions,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function statusForUser(User $user, string $stripeSessionId): ?array
    {
        $checkout = $this->checkoutSessionForUser($user, $stripeSessionId);
        if (null === $checkout || !$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return null;
        }

        if (OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus()) {
            $this->checkoutSync->syncPayment($checkout);
        }

        $order = null !== $checkout->getOrderId() ? $this->orders->find($checkout->getOrderId()) : null;

        return $this->payload($checkout, $order);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cancelForUser(User $user, string $stripeSessionId): ?array
    {
        $checkout = $this->checkoutSessionForUser($user, $stripeSessionId);
        if (null === $checkout || !$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return null;
        }

        if (OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus()) {
            $this->checkoutExpirer->expire($checkout);
            $checkout->markExpired('mobile_checkout_cancelled');
            $this->persistence->persist($checkout);
            $this->persistence->flush();
        }

        $order = null !== $checkout->getOrderId() ? $this->orders->find($checkout->getOrderId()) : null;

        return $this->payload($checkout, $order, false);
    }

    private function checkoutSessionForUser(User $user, string $stripeSessionId): ?OrderCheckoutSession
    {
        $checkout = $this->checkoutSessions->findOneByStripeSessionId($stripeSessionId);
        if (null === $checkout || !$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return null;
        }

        return $checkout;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(OrderCheckoutSession $checkout, ?Order $order, bool $includeOrder = true): array
    {
        return [
            'status' => $checkout->getStatus(),
            'checkoutSessionId' => $checkout->getStripeSessionId(),
            'orderId' => $order?->getId(),
            'order' => $includeOrder && null !== $order ? $this->formatter->formatOrder($order) : null,
        ];
    }
}
