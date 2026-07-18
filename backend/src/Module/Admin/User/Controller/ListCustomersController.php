<?php

declare(strict_types=1);

namespace App\Module\Admin\User\Controller;

use App\Module\User\Repository\UserRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers', name: 'api_admin_customers_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListCustomersController extends AbstractController
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $sort = (string) $request->query->get('sort', 'recent_order');

        return ApiResponse::success([
            'items' => $this->users->findAdminCustomerRows(
                is_string($search) ? $search : null,
                $sort,
                150,
            ),
        ]);
    }
}
