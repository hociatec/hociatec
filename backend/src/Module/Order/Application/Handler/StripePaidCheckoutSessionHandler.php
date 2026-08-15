<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Workflow\OrderService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Rental\Application\Workflow\CustomerRentalPortalService;
use App\Shared\Application\UnitOfWork;

final readonly class StripePaidCheckoutSessionHandler
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OrderService $orderCreator,
        private CustomerRentalPortalService $rentals,
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
                $this->rentals->applyPaidExtensionOrder($order);
            }

            return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
        }

        $order = $this->orderCreator->createFromCheckoutSession($checkout);
        $checkout->setOrderId($order->getId());
        $this->save($checkout);

        return ['type' => $type, 'sessionId' => $checkout->getStripeSessionId()];
    }

    private function save(object $entity): void
    {
        $this->persistence->persist($entity);
        $this->persistence->flush();
    }
}
