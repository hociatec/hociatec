<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Handler\StripeCheckoutSessionExpirer;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Rating\Application\Port\ProductRatingRepositoryPort;
use App\Shared\Application\UnitOfWork;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerOrderPortalService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private ProductRatingRepositoryPort $ratings,
        private OrderAccessPolicy $accessPolicy,
        private OrderFormatter $formatter,
        private OrderWorkflowService $workflow,
        private ?StripeCheckoutSessionSyncService $checkoutSync = null,
        private ?StripeCheckoutSessionExpirer $checkoutExpirer = null,
        private ?UnitOfWork $persistence = null,
        private ?OrderCheckoutSessionRepositoryPort $checkoutSessions = null,
    ) {
    }

    /**
     * @return array{items:list<array<string,mixed>>, total:int}
     */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        $orders = $this->orders->findByUser($user, $limit, $offset);
        $ratings = $this->collectRatings($orders);

        return [
            'items' => array_map(fn (Order $order): array => $this->formatter->formatOrder($order, $ratings), $orders),
            'total' => $this->orders->countByUser($user),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function showForUser(User $user, int $orderId): ?array
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order || !$this->accessPolicy->canView($user, $order)) {
            return null;
        }

        return $this->formatter->formatOrder($order, $this->collectRatings([$order]));
    }

    /**
     * @return array<string,mixed>|null
     */
    public function cancelForUser(User $user, int $orderId): ?array
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order || !$this->accessPolicy->canCancel($user, $order)) {
            return null;
        }

        if (Order::STATUS_PENDING !== $order->getStatus()) {
            throw new \InvalidArgumentException('Seules les commandes en attente peuvent etre annulees.');
        }

        $this->workflow->cancel($order);

        return $this->formatter->formatOrder($order);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function checkoutSessionStatusForUser(User $user, string $stripeSessionId): ?array
    {
        $checkout = $this->checkoutSessionForUser($user, $stripeSessionId);
        if (null === $checkout || !$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return null;
        }

        if (OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus()) {
            $this->checkoutSync?->syncPayment($checkout);
        }

        $order = null !== $checkout->getOrderId() ? $this->orders->find($checkout->getOrderId()) : null;

        return [
            'status' => $checkout->getStatus(),
            'checkoutSessionId' => $checkout->getStripeSessionId(),
            'orderId' => $order?->getId(),
            'order' => null !== $order ? $this->formatter->formatOrder($order) : null,
        ];
    }

    public function cancelCheckoutSessionForUser(User $user, string $stripeSessionId): ?array
    {
        $checkout = $this->checkoutSessionForUser($user, $stripeSessionId);
        if (null === $checkout || !$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return null;
        }

        if (OrderCheckoutSession::STATUS_OPEN === $checkout->getStatus()) {
            $this->checkoutExpirer?->expire($checkout);
            $checkout->markExpired('mobile_checkout_cancelled');
            $this->persistence?->persist($checkout);
            $this->persistence?->flush();
        }

        $order = null !== $checkout->getOrderId() ? $this->orders->find($checkout->getOrderId()) : null;

        return [
            'status' => $checkout->getStatus(),
            'checkoutSessionId' => $checkout->getStripeSessionId(),
            'orderId' => $order?->getId(),
            'order' => null !== $order ? $this->formatter->formatOrder($order) : null,
        ];
    }

    /**
     * @param list<Order> $orders
     *
     * @return array<int, mixed>
     */
    private function collectRatings(array $orders): array
    {
        $orderItemIds = [];
        foreach ($orders as $order) {
            foreach ($order->getItems() as $item) {
                if (null !== $item->getId()) {
                    $orderItemIds[] = $item->getId();
                }
            }
        }

        return $this->ratings->findByOrderItemIds($orderItemIds);
    }

    private function checkoutSessionForUser(User $user, string $stripeSessionId): ?OrderCheckoutSession
    {
        if (!$this->checkoutSessions instanceof OrderCheckoutSessionRepositoryPort) {
            throw new \LogicException('Checkout session repository is not configured.');
        }

        $checkout = $this->checkoutSessions->findOneByStripeSessionId($stripeSessionId);
        if (null === $checkout || !$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return null;
        }

        return $checkout;
    }
}
