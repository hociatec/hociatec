<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Rating\Application\Port\ProductRatingRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/me', name: 'api_orders_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyOrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly ProductRatingRepositoryPort $ratings,
        private readonly OrderFormatter $orderFormatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $orders = $this->orders->findByUser($user, $pagination->perPage, $pagination->offset());
        $total = $this->orders->countByUser($user);
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
            fn ($o) => $this->orderFormatter->formatOrder($o, $ratings),
            $orders,
        );

        return ApiResponse::paginated($items, $pagination->metadata($total));
    }
}
