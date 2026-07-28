<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Entity\BugReport;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\BetaTest\Service\BugReportActivityLogger;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
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
        private readonly DoctrinePersistence $persistence,
        private readonly BugReportActivityLogger $activityLogger,
        private readonly UserCommunicationNotifier $notifier,
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
        if ($duplicateOfId <= 0 || $duplicateOfId === $id) {
            return ApiResponse::error('Sélectionnez un autre signalement de référence.', 422);
        }

        $duplicateOf = $this->reports->find($duplicateOfId);
        if (!$duplicateOf instanceof BugReport) {
            return ApiResponse::error('Signalement de référence introuvable.', 404);
        }

        $reason = trim((string) ($payload['reason'] ?? ''));
        $previous = $report->getDuplicateOf()?->getId();
        $report->markDuplicateOf($duplicateOf, $reason);
        $actor = $this->getUser();
        $this->activityLogger->log($report, $actor instanceof User ? $actor : null, 'marked_duplicate', null !== $previous ? (string) $previous : null, (string) $duplicateOfId, $reason);
        $this->persistence->flush();

        $this->notifier->notify(
            $report->getReporter(),
            sprintf('beta-report-duplicate:%d:%d', $report->getId(), $duplicateOfId),
            'Signalement bêta marqué comme doublon',
            sprintf('Le signalement « %s » est rattaché au signalement « %s ».', $report->getTitle(), $duplicateOf->getTitle()),
            sprintf('/beta?reportId=%d', $report->getId()),
            'beta_report_status',
        );

        return ApiResponse::success(['id' => $report->getId()], 200, 'Signalement marqué comme doublon.');
    }
}
