<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Dashboard\Controller;

use App\Module\Admin\Application\Dashboard\Provider\DashboardDataProvider;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class GetDashboardController extends AbstractController
{
    public function __construct(private readonly DashboardDataProvider $dashboard)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success($this->dashboard->provide());
    }
}
