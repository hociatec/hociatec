<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Writer;

use App\Module\Order\Application\DTO\DeliveryInput;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Enum\DeliveryStatus;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;
use App\Shared\Infrastructure\DateTime\DateTimeParser;

final readonly class OrderDeliveryUpdater
{
    public function __construct(
        private UnitOfWork $persistence,
        private OrderEventLogger $events,
    ) {
    }

    public function update(Order $order, DeliveryInput $input, ?User $actor): Order
    {
        $status = '' !== $input->status ? $input->status : $order->getDeliveryStatus();
        if (null === DeliveryStatus::tryFrom($status)) {
            throw new \InvalidArgumentException('Étape de livraison invalide.');
        }

        $carrier = $input->carrier ?? $order->getDeliveryCarrier();
        $trackingNumber = $input->trackingNumber ?? $order->getDeliveryTrackingNumber();
        $trackingUrl = $input->trackingUrl?->value() ?? $order->getDeliveryTrackingUrl();
        $estimatedAt = null !== $input->estimatedAt
            ? $this->nullableDate($input->estimatedAt)
            : $order->getDeliveryEstimatedAt();
        $before = $this->snapshot($order);
        $order
            ->setDeliveryStatus($status)
            ->setDeliveryCarrier($carrier)
            ->setDeliveryTrackingNumber($trackingNumber)
            ->setDeliveryTrackingUrl($trackingUrl)
            ->setDeliveryEstimatedAt($estimatedAt);
        $this->applyStatusDates($order, $status);

        $this->persistence->persist($order);
        $this->persistence->commit();
        $this->events->log($order, $actor, 'delivery_updated', $this->changeMessage($before, $order));

        return $order;
    }

    private function applyStatusDates(Order $order, string $status): void
    {
        if (in_array($status, [
            Order::DELIVERY_STATUS_SHIPPED,
            Order::DELIVERY_STATUS_IN_TRANSIT,
            Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
            Order::DELIVERY_STATUS_DELIVERED,
        ], true) && null === $order->getDeliveryShippedAt()) {
            $order->setDeliveryShippedAt(new \DateTimeImmutable());
        }

        if (Order::DELIVERY_STATUS_DELIVERED === $status) {
            $order->setDeliveryDeliveredAt($order->getDeliveryDeliveredAt() ?? new \DateTimeImmutable());
            if (Order::STATUS_DELIVERED !== $order->getStatus()) {
                $order->setStatus(Order::STATUS_DELIVERED);
            }
        }
    }

    /**
     * @return array{status: string, carrier: ?string, trackingNumber: ?string, trackingUrl: ?string, estimatedAt: ?string}
     */
    private function snapshot(Order $order): array
    {
        return [
            'status' => $order->getDeliveryStatus(),
            'carrier' => $order->getDeliveryCarrier(),
            'trackingNumber' => $order->getDeliveryTrackingNumber(),
            'trackingUrl' => $order->getDeliveryTrackingUrl(),
            'estimatedAt' => $order->getDeliveryEstimatedAt()?->format('Y-m-d'),
        ];
    }

    /**
     * @param array{status: string, carrier: ?string, trackingNumber: ?string, trackingUrl: ?string, estimatedAt: ?string} $before
     */
    private function changeMessage(array $before, Order $order): string
    {
        $changes = [];
        $this->addChange($changes, $before['status'], $order->getDeliveryStatus(), 'étape');
        $this->addChange($changes, $before['carrier'], $order->getDeliveryCarrier(), 'transporteur');
        $this->addChange($changes, $before['trackingNumber'], $order->getDeliveryTrackingNumber(), 'suivi');
        if ($before['trackingUrl'] !== $order->getDeliveryTrackingUrl()) {
            $changes[] = 'lien de suivi mis à jour';
        }
        $this->addChange($changes, $before['estimatedAt'], $order->getDeliveryEstimatedAt()?->format('Y-m-d'), 'date estimée');

        return [] === $changes
            ? 'Suivi livraison mis à jour.'
            : 'Suivi livraison mis à jour: '.implode(', ', $changes).'.';
    }

    /**
     * @param list<string> $changes
     */
    private function addChange(array &$changes, ?string $before, ?string $after, string $label): void
    {
        if ($before !== $after) {
            $changes[] = sprintf('%s "%s" -> "%s"', $label, $before ?? '-', $after ?? '-');
        }
    }

    private function nullableDate(mixed $value): ?\DateTimeImmutable
    {
        $normalized = trim((string) $value);
        if ('' === $normalized) {
            return null;
        }

        $date = DateTimeParser::fromFormat('!Y-m-d', $normalized);
        if (!$date instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException('Date estimée invalide.');
        }

        return $date;
    }
}
