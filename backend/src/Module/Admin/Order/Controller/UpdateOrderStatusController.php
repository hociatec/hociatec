<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\Controller;

use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\StateMachineInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Module\Order\Message\OrderStatusChangedMessage;

#[Route('/api/admin/orders/{orderId}/status', name: 'api_admin_orders_update_status', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateOrderStatusController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EntityManagerInterface $em,
        #[Autowire(service: 'state_machine.order_status')]
        private readonly StateMachineInterface $stateMachine,
        private readonly MessageBusInterface $bus,
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

        // Map desired status to transition and apply via state machine
        $transition = match ($status) {
            Order::STATUS_CONFIRMED => 'confirm',
            Order::STATUS_DELIVERED => 'deliver',
            Order::STATUS_CANCELLED => 'cancel',
            Order::STATUS_PENDING => null, // no-op or invalid from other states
            default => null,
        };

        if ($transition === null || !$this->stateMachine->can($order, $transition)) {
            return ApiResponse::error('Transition de statut invalide.', Response::HTTP_CONFLICT);
        }

        $this->stateMachine->apply($order, $transition);
        $this->em->persist($order);
        $this->em->flush();

        $this->bus->dispatch(new OrderStatusChangedMessage($order->getId() ?? 0, $order->getNumber(), $old, $order->getStatus()))
        ;

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }
}
