<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderDeliveryUpdater
{
    private const STATUSES = [
        Order::DELIVERY_STATUS_PREPARING,
        Order::DELIVERY_STATUS_SHIPPED,
        Order::DELIVERY_STATUS_IN_TRANSIT,
        Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
        Order::DELIVERY_STATUS_DELIVERED,
        Order::DELIVERY_STATUS_ISSUE,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderEventLogger $events,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Order $order, array $payload, ?User $actor): Order
    {
        $status = isset($payload['status']) ? trim((string) $payload['status']) : $order->getDeliveryStatus();
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Étape de livraison invalide.');
        }

        $carrier = $this->nullableString($payload['carrier'] ?? $order->getDeliveryCarrier());
        $trackingNumber = $this->nullableString($payload['trackingNumber'] ?? $order->getDeliveryTrackingNumber());
        $trackingUrl = $this->nullableString($payload['trackingUrl'] ?? $order->getDeliveryTrackingUrl());
        $estimatedAt = array_key_exists('estimatedAt', $payload)
            ? $this->nullableDate($payload['estimatedAt'])
            : $order->getDeliveryEstimatedAt();
        if (null !== $trackingUrl && false === filter_var($trackingUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Lien de suivi invalide.');
        }

        $before = $this->snapshot($order);
        $order
            ->setDeliveryStatus($status)
            ->setDeliveryCarrier($carrier)
            ->setDeliveryTrackingNumber($trackingNumber)
            ->setDeliveryTrackingUrl($trackingUrl)
            ->setDeliveryEstimatedAt($estimatedAt);
        $this->applyStatusDates($order, $status);

        $this->entityManager->persist($order);
        $this->entityManager->flush();
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

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return '' !== $normalized ? $normalized : null;
    }

    private function nullableDate(mixed $value): ?\DateTimeImmutable
    {
        $normalized = trim((string) $value);
        if ('' === $normalized) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $normalized);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date instanceof \DateTimeImmutable || (false !== $errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException('Date estimée invalide.');
        }

        return $date;
    }
}
