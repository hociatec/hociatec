<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\Controller;

use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/delivery', name: 'api_admin_orders_update_delivery', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateOrderDeliveryController extends AbstractController
{
    private const DELIVERY_STATUSES = [
        Order::DELIVERY_STATUS_PREPARING,
        Order::DELIVERY_STATUS_SHIPPED,
        Order::DELIVERY_STATUS_IN_TRANSIT,
        Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
        Order::DELIVERY_STATUS_DELIVERED,
        Order::DELIVERY_STATUS_ISSUE,
    ];

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EntityManagerInterface $em,
        private readonly OrderEventLogger $events,
    ) {
    }

    public function __invoke(int $orderId, Request $request): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if ($order === null) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $status = isset($payload['status']) ? trim((string) $payload['status']) : $order->getDeliveryStatus();
            if (!in_array($status, self::DELIVERY_STATUSES, true)) {
                return ApiResponse::error('Étape de livraison invalide.', Response::HTTP_BAD_REQUEST);
            }

            $carrier = $this->normalizeNullableString($payload['carrier'] ?? $order->getDeliveryCarrier());
            $trackingNumber = $this->normalizeNullableString($payload['trackingNumber'] ?? $order->getDeliveryTrackingNumber());
            $trackingUrl = $this->normalizeNullableString($payload['trackingUrl'] ?? $order->getDeliveryTrackingUrl());
            $estimatedAt = array_key_exists('estimatedAt', $payload)
                ? $this->parseNullableDate($payload['estimatedAt'])
                : $order->getDeliveryEstimatedAt();
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if (
            $trackingUrl !== null
            && filter_var($trackingUrl, FILTER_VALIDATE_URL) === false
        ) {
            return ApiResponse::error('Lien de suivi invalide.', Response::HTTP_BAD_REQUEST);
        }

        $oldStatus = $order->getDeliveryStatus();
        $oldCarrier = $order->getDeliveryCarrier();
        $oldTrackingNumber = $order->getDeliveryTrackingNumber();
        $oldTrackingUrl = $order->getDeliveryTrackingUrl();
        $oldEstimatedAt = $order->getDeliveryEstimatedAt()?->format('Y-m-d');

        $order
            ->setDeliveryStatus($status)
            ->setDeliveryCarrier($carrier)
            ->setDeliveryTrackingNumber($trackingNumber)
            ->setDeliveryTrackingUrl($trackingUrl)
            ->setDeliveryEstimatedAt($estimatedAt);

        if (
            in_array($status, [
                Order::DELIVERY_STATUS_SHIPPED,
                Order::DELIVERY_STATUS_IN_TRANSIT,
                Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
                Order::DELIVERY_STATUS_DELIVERED,
            ], true)
            && $order->getDeliveryShippedAt() === null
        ) {
            $order->setDeliveryShippedAt(new \DateTimeImmutable());
        }

        if ($status === Order::DELIVERY_STATUS_DELIVERED) {
            if ($order->getDeliveryDeliveredAt() === null) {
                $order->setDeliveryDeliveredAt(new \DateTimeImmutable());
            }
            if ($order->getStatus() !== Order::STATUS_DELIVERED) {
                $order->setStatus(Order::STATUS_DELIVERED);
            }
        }

        $this->em->persist($order);
        $this->em->flush();

        $changes = [];
        if ($oldStatus !== $status) {
            $changes[] = sprintf('étape %s -> %s', $oldStatus, $status);
        }
        if ($oldCarrier !== $carrier) {
            $changes[] = sprintf('transporteur "%s" -> "%s"', $oldCarrier ?? '-', $carrier ?? '-');
        }
        if ($oldTrackingNumber !== $trackingNumber) {
            $changes[] = sprintf('suivi "%s" -> "%s"', $oldTrackingNumber ?? '-', $trackingNumber ?? '-');
        }
        if ($oldTrackingUrl !== $trackingUrl) {
            $changes[] = 'lien de suivi mis à jour';
        }
        if ($oldEstimatedAt !== $estimatedAt?->format('Y-m-d')) {
            $changes[] = sprintf('date estimée "%s" -> "%s"', $oldEstimatedAt ?? '-', $estimatedAt?->format('Y-m-d') ?? '-');
        }

        $actor = $this->getUser();
        $this->events->log(
            $order,
            $actor instanceof \App\Module\User\Entity\User ? $actor : null,
            'delivery_updated',
            $changes === [] ? 'Suivi livraison mis à jour.' : 'Suivi livraison mis à jour: ' . implode(', ', $changes) . '.',
        );

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function parseNullableDate(mixed $value): ?\DateTimeImmutable
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $normalized);
        if (!$date instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException('Date estimée invalide.');
        }

        return $date->setTime(0, 0);
    }
}
