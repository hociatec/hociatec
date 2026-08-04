<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/dashboard', name: 'api_admin_beta_reports_dashboard', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class BugReportDashboardController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly BetaCampaignRepository $campaigns,
        private readonly UserRepository $users,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $stats = $this->reports->dashboardStats();
        $activeCampaigns = array_filter(
            $this->campaigns->findBy([], ['createdAt' => 'DESC']),
            static fn (BetaCampaign $campaign): bool => BetaCampaign::STATUS_ACTIVE === $campaign->getEffectiveStatus(),
        );

        return ApiResponse::success([
            'stats' => [
                ...$stats,
                'activeCampaigns' => count($activeCampaigns),
            ],
            'admins' => array_map(static fn ($admin) => [
                'id' => $admin->getId(),
                'name' => $admin->getFullName(),
                'email' => $admin->getEmail(),
            ], $this->users->findAdmins()),
        ]);
    }
}
