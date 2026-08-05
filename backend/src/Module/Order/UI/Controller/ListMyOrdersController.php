<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Rating\Application\Port\ProductRatingRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/me', name: 'api_orders_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyOrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly ProductRatingRepositoryPort $ratings,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $this->orders->findByUser($user);
        $orderItemIds = [];
        foreach ($orders as $order) {
            foreach ($order->getItems() as $item) {
                if (null !== $item->getId()) {
                    $orderItemIds[] = $item->getId();
                }
            }
        }

        $ratings = $this->ratings->findByOrderItemIds($orderItemIds);

        $items = array_map(
            fn ($o) => OrderFormatter::formatOrder($o, $ratings),
            $orders,
        );

        return ApiResponse::successItem('items', $items);
    }
}
