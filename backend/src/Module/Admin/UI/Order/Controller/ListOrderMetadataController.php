<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Order\Application\Projection\OrderFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/metadata', name: 'api_admin_orders_metadata', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListOrderMetadataController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::successItem('statuses', OrderFormatter::statusOptions());
    }
}
