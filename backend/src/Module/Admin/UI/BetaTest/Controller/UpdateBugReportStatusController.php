<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\JsonPayload;
use App\Module\Admin\Application\BetaTest\Service\ChangeBugReportStatusHandler;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}/status', name: 'api_admin_beta_reports_status', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateBugReportStatusController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly ChangeBugReportStatusHandler $changeBugReportStatus,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $payload = JsonPayload::decode($request);
        $status = trim((string) ($payload['status'] ?? ''));

        $admin = $this->getUser();
        $actor = $admin instanceof User ? $admin : null;
        try {
            $this->changeBugReportStatus->change($report, $status, $actor);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success([
            'id' => $report->getId(),
            'status' => $report->getStatus(),
        ], 200, 'État mis à jour.');
    }
}
