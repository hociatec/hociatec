<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/metadata', name: 'api_admin_orders_metadata', methods: ['GET'])]
#[IsGranted('ROLE_ORDERS_MANAGER')]
final class ListOrderMetadataController extends AbstractController
{
    public function __construct(private readonly OrderFormatter $orderFormatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::successItem('statuses', $this->orderFormatter->statusOptions());
    }
}
