<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\User\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\User\Infrastructure\Repository\UserRepository;
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
        $pagination = Pagination::fromRequest($request, 25, 100);

        $total = $this->users->countAdminCustomerRows(is_string($search) ? $search : null);

        return ApiResponse::paginated(
            $this->users->findAdminCustomerRows(
                is_string($search) ? $search : null,
                $sort,
                $pagination->perPage,
                $pagination->offset(),
            ),
            $pagination->metadata($total),
        );
    }
}
