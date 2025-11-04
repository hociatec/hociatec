<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\Controller;

use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders', name: 'api_admin_orders_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListOrdersController extends AbstractController
{
    public function __construct(private readonly OrderRepository $orders)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $status = $request->query->get('status');

        $qb = $this->orders->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC');

        if (is_string($status) && $status !== '' && $status !== 'all') {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        $items = array_map(
            fn(Order $o) => OrderFormatter::formatOrder($o),
            $qb->getQuery()->getResult(),
        );

        return ApiResponse::success(['items' => $items]);
    }
}

