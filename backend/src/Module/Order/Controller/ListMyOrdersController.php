<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/me', name: 'api_orders_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyOrdersController extends AbstractController
{
    public function __construct(private readonly OrderRepository $orders)
    {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $items = array_map(
            fn($o) => OrderFormatter::formatOrder($o),
            $this->orders->findByUser($user),
        );

        return ApiResponse::success(['items' => $items]);
    }
}

