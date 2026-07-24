<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Service;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FulfillmentOperationsService
{
    public function __construct(
        private OrderRepository $orders,
        private EntityManagerInterface $entityManager,
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

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function ship(int $orderId, array $payload, ?User $actor): array
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order) {
            throw new OperationsResourceNotFoundException('Commande introuvable.');
        }

        $carrier = $this->nullableString($payload['carrier'] ?? $order->getDeliveryCarrier());
        $trackingNumber = $this->nullableString($payload['trackingNumber'] ?? $order->getDeliveryTrackingNumber());
        $trackingUrl = $this->nullableString($payload['trackingUrl'] ?? $order->getDeliveryTrackingUrl());
        if (null !== $trackingUrl && false === filter_var($trackingUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Lien de suivi invalide.');
        }

        $order
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_SHIPPED)
            ->setDeliveryCarrier($carrier)
            ->setDeliveryTrackingNumber($trackingNumber)
            ->setDeliveryTrackingUrl($trackingUrl);

        if (null === $order->getDeliveryShippedAt()) {
            $order->setDeliveryShippedAt(new \DateTimeImmutable());
        }
        $this->entityManager->flush();

        $this->events->log(
            $order,
            $actor,
            'order_shipped',
            sprintf('Commande marquée expédiée%s.', null !== $trackingNumber ? ' avec suivi '.$trackingNumber : ''),
        );

        return $this->formatter->fulfillmentOrder($order);
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return '' !== $normalized ? $normalized : null;
    }
}
