<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\Admin\BetaTest\Service\AdminBugReportManager;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}', name: 'api_admin_beta_reports_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
final class DeleteBugReportController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly AdminBugReportManager $reportManager,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $this->reportManager->delete($report);

        return ApiResponse::success([], 200, 'Rapport supprimé.');
    }
}
