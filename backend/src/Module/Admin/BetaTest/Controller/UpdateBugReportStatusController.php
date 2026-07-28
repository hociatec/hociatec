<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
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
        private readonly DoctrinePersistence $persistence,
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
        $status = trim((string) ($payload['status'] ?? ''));

        $allowedStatuses = ['submitted', 'under_review', 'resolved', 'closed', 'rejected'];
        if (!in_array($status, $allowedStatuses, true)) {
            return ApiResponse::error('État invalide.', 422);
        }

        $previousStatus = $report->getStatus();
        $report->setStatus($status);
        $this->persistence->flush();

        if ($previousStatus !== $status) {
            $changedAt = new \DateTimeImmutable();
            $this->notifier->notify(
                $report->getReporter(),
                sprintf('beta-report-status:%d:%s:%s', $report->getId(), $status, $changedAt->format('Uu')),
                'État d’un signalement bêta mis à jour',
                sprintf('Titre du signalement : %s. Nouvel état : %s.', $report->getTitle(), $this->statusLabel($status)),
                '/beta',
                'beta_report_status',
            );
        }

        return ApiResponse::success([
            'id' => $report->getId(),
            'status' => $report->getStatus(),
        ], 200, 'État mis à jour.');
    }

    private function statusLabel(string $status): string
    {
        return [
            'submitted' => 'Soumis',
            'under_review' => 'En cours d’analyse',
            'resolved' => 'Corrigé',
            'closed' => 'Fermé',
            'rejected' => 'Rejeté',
        ][$status] ?? $status;
    }
}
