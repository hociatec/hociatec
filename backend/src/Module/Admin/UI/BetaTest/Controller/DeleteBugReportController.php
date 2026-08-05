<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\Handler\DeleteBugReportHandler;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}', name: 'api_admin_beta_reports_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_BETA_MANAGER')]
final class DeleteBugReportController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepositoryPort $reports,
        private readonly DeleteBugReportHandler $deleteBugReport,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $this->deleteBugReport->delete($report);

        return ApiResponse::success([], 200, 'Rapport supprimé.');
    }
}
