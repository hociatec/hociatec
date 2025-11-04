<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}/cancel', name: 'api_orders_cancel', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CancelMyOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(int $orderId): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if ($order === null) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($order->getUser()->getId() !== $user->getId()) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($order->getStatus() !== Order::STATUS_PENDING) {
            return ApiResponse::error('Seules les commandes en attente peuvent etre annulees.', Response::HTTP_BAD_REQUEST);
        }

        $order->setStatus(Order::STATUS_CANCELLED);
        $this->em->persist($order);
        $this->em->flush();

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }
}

