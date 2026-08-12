<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\User\Controller;

use App\Module\Admin\Application\User\Provider\CustomerDetailsProvider;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}', name: 'api_admin_customers_show', methods: ['GET'])]
#[IsGranted('ROLE_CUSTOMERS_MANAGER')]
final class ShowCustomerController extends AbstractController
{
    public function __construct(private readonly CustomerDetailsProvider $customers)
    {
    }

    public function __invoke(Request $request, int $userId): JsonResponse
    {
        $orderStatus = (string) $request->query->get('orderStatus', 'all');
        $orderPage = max(1, (int) $request->query->get('orderPage', 1));
        $orderPerPage = max(1, min(100, (int) $request->query->get('orderPerPage', 10)));
        $details = $this->customers->details($userId, $orderStatus, $orderPage, $orderPerPage);
        if (null === $details) {
            return ApiResponse::error('Client introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($details);
    }
}
