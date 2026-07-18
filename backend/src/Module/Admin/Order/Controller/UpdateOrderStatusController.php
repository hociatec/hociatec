<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\Controller;

use App\Module\Order\Entity\Order;
use App\Module\Order\Message\OrderStatusChangedMessage;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/api/admin/orders/{orderId}/status', name: 'api_admin_orders_update_status', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateOrderStatusController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EntityManagerInterface $em,
        #[Autowire(service: 'state_machine.order_status')]
        private readonly WorkflowInterface $stateMachine,
        private readonly MessageBusInterface $bus,
        private readonly OrderEventLogger $events,
    ) {
    }

    public function __invoke(int $orderId, Request $request): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if ($order === null) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $request->toArray();
        $status = $payload['status'] ?? null;
        if (!\is_string($status)) {
            return ApiResponse::error('Statut invalide.', Response::HTTP_BAD_REQUEST);
        }

        $allowed = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];

        if (!\in_array($status, $allowed, true)) {
            return ApiResponse::error('Statut invalide.', Response::HTTP_BAD_REQUEST);
        }

        $old = $order->getStatus();

        $transition = match ($status) {
            Order::STATUS_CONFIRMED => 'confirm',
            Order::STATUS_DELIVERED => 'deliver',
            Order::STATUS_CANCELLED => 'cancel',
            Order::STATUS_PENDING => null,
            default => null,
        };

        if ($transition === null || !$this->stateMachine->can($order, $transition)) {
            return ApiResponse::error('Transition de statut invalide.', Response::HTTP_CONFLICT);
        }

        $this->stateMachine->apply($order, $transition);

        if ($status === Order::STATUS_CANCELLED) {
            $order->setInvoiceStatus(Order::INVOICE_STATUS_CANCELLED);
        } elseif ($status === Order::STATUS_DELIVERED) {
            $order->setDeliveryStatus(Order::DELIVERY_STATUS_DELIVERED);
            if ($order->getDeliveryDeliveredAt() === null) {
                $order->setDeliveryDeliveredAt(new \DateTimeImmutable());
            }
            if ($order->getDeliveryShippedAt() === null) {
                $order->setDeliveryShippedAt(new \DateTimeImmutable());
            }
        } elseif ($status === Order::STATUS_CONFIRMED && $order->getDeliveryStatus() === '') {
            $order->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING);
        }

        $this->em->persist($order);
        $this->em->flush();
        $actor = $this->getUser();
        $this->events->log(
            $order,
            $actor instanceof \App\Module\User\Entity\User ? $actor : null,
            'status_changed',
            sprintf(
                'Statut : %s -> %s',
                OrderFormatter::formatStatusLabel($old),
                OrderFormatter::formatStatusLabel($order->getStatus()),
            ),
        );

        $this->bus->dispatch(new OrderStatusChangedMessage($order->getId() ?? 0, $order->getNumber(), $old, $order->getStatus()));

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }
}
