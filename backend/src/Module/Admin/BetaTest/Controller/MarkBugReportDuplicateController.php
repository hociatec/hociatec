<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\Admin\BetaTest\Service\AdminBugReportManager;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}/duplicate', name: 'api_admin_beta_reports_duplicate', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class MarkBugReportDuplicateController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly AdminBugReportManager $reportManager,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $payload = JsonPayload::decode($request);
        $duplicateOfId = (int) ($payload['duplicateOfId'] ?? 0);
        $reason = trim((string) ($payload['reason'] ?? ''));
        $actor = $this->getUser();

        try {
            $duplicateOf = $this->reportManager->referenceReport($duplicateOfId, $id);
            $this->reportManager->markDuplicate($report, $duplicateOf, $reason, $actor instanceof User ? $actor : null);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 404);
        }

        return ApiResponse::success(['id' => $report->getId()], 200, 'Signalement marqué comme doublon.');
    }
}
