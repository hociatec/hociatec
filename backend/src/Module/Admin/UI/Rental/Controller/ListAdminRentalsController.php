<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Rental\Controller;

use App\Module\Admin\Application\Rental\Workflow\AdminRentalManagementService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/rentals', name: 'api_admin_rentals_list', methods: ['GET'])]
#[IsGranted('ROLE_ORDERS_MANAGER')]
final class ListAdminRentalsController extends AbstractController
{
    public function __construct(private readonly AdminRentalManagementService $rentals)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 20, 100);
        $search = RequestQueryMapper::nullableString($request, 'q');
        $timeline = RequestQueryMapper::choice($request, 'timeline', ['all', 'upcoming', 'active', 'past'], 'all');
        $requestStatus = RequestQueryMapper::choice($request, 'requestStatus', ['all', 'none', 'pending'], 'all');
        $requestType = RequestQueryMapper::choice($request, 'requestType', ['all', 'extend', 'end_early'], 'all');
        $result = $this->rentals->list(
            $search,
            'all' === $timeline ? null : $timeline,
            $requestStatus,
            $requestType,
            $pagination->perPage,
            $pagination->offset(),
        );

        return ApiResponse::paginated($result['items'], $pagination->metadata($result['total']));
    }
}
