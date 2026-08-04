<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Order\Application\Service\OrderFormatter;
use App\Module\Order\Application\Service\OrderWorkflowService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Domain\Entity\User;
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
        private readonly OrderWorkflowService $workflow,
        private readonly OrderAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(int $orderId): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$this->accessPolicy->canCancel($user, $order)) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (Order::STATUS_PENDING !== $order->getStatus()) {
            return ApiResponse::error('Seules les commandes en attente peuvent etre annulees.', Response::HTTP_BAD_REQUEST);
        }

        $this->workflow->cancel($order);

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }
}
