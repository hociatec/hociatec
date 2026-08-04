<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Order\Application\Message\OrderStatusChangedMessage;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class OrderStatusUpdater
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        #[Autowire(service: 'state_machine.order_status')]
        private WorkflowInterface $stateMachine,
        private MessageBusInterface $bus,
        private OrderEventLogger $events,
    ) {
    }

    public function update(Order $order, string $status, ?User $actor): Order
    {
        if (!in_array($status, (new OrderStatusWorkflow())->statuses(), true)) {
            throw new \InvalidArgumentException('Statut invalide.');
        }

        $transition = match ($status) {
            Order::STATUS_CONFIRMED => 'confirm',
            Order::STATUS_DELIVERED => 'deliver',
            Order::STATUS_CANCELLED => 'cancel',
            default => null,
        };
        if (null === $transition || !$this->stateMachine->can($order, $transition)) {
            throw new \DomainException('Transition de statut invalide.');
        }

        $oldStatus = $order->getStatus();
        $this->stateMachine->apply($order, $transition);
        $this->synchronizeOperationalState($order, $status);
        $this->persistence->persist($order);
        $this->persistence->commit();

        $this->events->log(
            $order,
            $actor,
            'status_changed',
            sprintf(
                'Statut : %s -> %s',
                OrderFormatter::formatStatusLabel($oldStatus),
                OrderFormatter::formatStatusLabel($order->getStatus()),
            ),
        );
        $this->bus->dispatch(new OrderStatusChangedMessage(
            $order->getId() ?? 0,
            $order->getNumber(),
            $oldStatus,
            $order->getStatus(),
        ));

        return $order;
    }

    private function synchronizeOperationalState(Order $order, string $status): void
    {
        if (Order::STATUS_CANCELLED === $status) {
            $order->setInvoiceStatus(Order::INVOICE_STATUS_CANCELLED);

            return;
        }
        if (Order::STATUS_DELIVERED === $status) {
            $order
                ->setDeliveryStatus(Order::DELIVERY_STATUS_DELIVERED)
                ->setDeliveryDeliveredAt($order->getDeliveryDeliveredAt() ?? new \DateTimeImmutable())
                ->setDeliveryShippedAt($order->getDeliveryShippedAt() ?? new \DateTimeImmutable());

            return;
        }
        if (Order::STATUS_CONFIRMED === $status && '' === $order->getDeliveryStatus()) {
            $order->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING);
        }
    }
}
