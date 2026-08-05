<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Workflow;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\DTO\DeliveryInput;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;

final readonly class FulfillmentOperationsService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OperationsPersistence $persistence,
        private OrderEventLogger $events,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function queue(): array
    {
        return array_map($this->formatter->fulfillmentOrder(...), $this->orders->findFulfillmentQueue(50));
    }

    /** @return array<string, mixed> */
    public function ship(int $orderId, DeliveryInput $input, ?User $actor): array
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order) {
            throw new OperationsResourceNotFoundException('Commande introuvable.');
        }

        $carrier = $input->carrier ?? $order->getDeliveryCarrier();
        $trackingNumber = $input->trackingNumber ?? $order->getDeliveryTrackingNumber();
        $trackingUrl = $input->trackingUrl?->value() ?? $order->getDeliveryTrackingUrl();

        $order
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_SHIPPED)
            ->setDeliveryCarrier($carrier)
            ->setDeliveryTrackingNumber($trackingNumber)
            ->setDeliveryTrackingUrl($trackingUrl);

        if (null === $order->getDeliveryShippedAt()) {
            $order->setDeliveryShippedAt(new \DateTimeImmutable());
        }
        $this->persistence->commit();

        $this->events->log(
            $order,
            $actor,
            'order_shipped',
            sprintf('Commande marquée expédiée%s.', null !== $trackingNumber ? ' avec suivi '.$trackingNumber : ''),
        );

        return $this->formatter->fulfillmentOrder($order);
    }
}
