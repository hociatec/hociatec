<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Security\OrderAccessPolicy;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Rating\Repository\ProductRatingRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
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
        private readonly OrderRepository $orders,
        private readonly ProductRatingRepository $ratings,
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

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order, $ratings)]);
    }
}
