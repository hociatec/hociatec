<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Rating\Application\Port\ProductRatingRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}', name: 'api_orders_show', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ShowOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly ProductRatingRepositoryPort $ratings,
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
        if (!$this->accessPolicy->canView($user, $order)) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $orderItemIds = [];
        foreach ($order->getItems() as $item) {
            if (null !== $item->getId()) {
                $orderItemIds[] = $item->getId();
            }
        }
        $ratings = $this->ratings->findByOrderItemIds($orderItemIds);

        return ApiResponse::successItem('order', OrderFormatter::formatOrder($order, $ratings));
    }
}
