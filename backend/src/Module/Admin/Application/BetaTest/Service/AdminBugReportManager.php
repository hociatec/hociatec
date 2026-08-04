<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrinePersistence;
use App\Module\BetaTest\Application\Service\BetaAttachmentStorage;
use App\Module\BetaTest\Application\Service\BugReportActivityLogger;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;

final readonly class AdminBugReportManager
{
    public function __construct(
        private DoctrinePersistence $persistence,
        private BugReportActivityLogger $activityLogger,
        private UserCommunicationNotifier $notifier,
        private BetaAttachmentStorage $attachments,
        private BugReportRepository $reports,
        private BugReportAccessPolicy $accessPolicy,
    ) {
    }

    public function assign(BugReport $report, ?User $assignedTo, ?User $actor): void
    {
        if (null !== $assignedTo && !$this->accessPolicy->isAdmin($assignedTo)) {
            throw new \InvalidArgumentException('Administrateur introuvable.');
        }

        $previous = $report->getAssignedTo()?->getEmail();
        $report->assignTo($assignedTo);
        $this->activityLogger->log($report, $actor, 'assignment_changed', $previous, $assignedTo?->getEmail());
        $this->persistence->flush();
    }

    public function updateStatus(BugReport $report, string $status, ?User $actor): void
    {
        if (!in_array($status, BugReport::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException('État invalide.');
        }

        $previousStatus = $report->getStatus();
        $report->setStatus($status);
        if ($previousStatus !== $status) {
            $this->activityLogger->log($report, $actor, 'status_changed', $previousStatus, $status);
        }
        $this->persistence->flush();

        if ($previousStatus !== $status) {
            $changedAt = new \DateTimeImmutable();
            $this->notifier->notify(
                $report->getReporter(),
                sprintf('beta-report-status:%d:%s:%s', $report->getId(), $status, $changedAt->format('Uu')),
                'État d’un signalement bêta mis à jour',
                sprintf('Titre du signalement : %s. Nouvel état : %s.', $report->getTitle(), $this->statusLabel($status)),
                sprintf('/beta?reportId=%d', $report->getId()),
                'beta_report_status',
            );
        }
    }

    public function markDuplicate(BugReport $report, BugReport $duplicateOf, string $reason, ?User $actor): void
    {
        if ($duplicateOf->getId() === $report->getId()) {
            throw new \InvalidArgumentException('Sélectionnez un autre signalement de référence.');
        }

        $previous = $report->getDuplicateOf()?->getId();
        $report->markDuplicateOf($duplicateOf, $reason);
        $this->activityLogger->log($report, $actor, 'marked_duplicate', null !== $previous ? (string) $previous : null, (string) $duplicateOf->getId(), $reason);
        $this->persistence->flush();
        $this->notifier->notify(
            $report->getReporter(),
            sprintf('beta-report-duplicate:%d:%d', $report->getId(), $duplicateOf->getId()),
            'Signalement bêta marqué comme doublon',
            sprintf('Le signalement « %s » est rattaché au signalement « %s ».', $report->getTitle(), $duplicateOf->getTitle()),
            sprintf('/beta?reportId=%d', $report->getId()),
            'beta_report_status',
        );
    }

    public function delete(BugReport $report): void
    {
        $attachmentNames = $report->getAttachments();
        $this->persistence->remove($report);
        $this->persistence->flush();
        $this->attachments->deleteMany($attachmentNames);
    }

    public function referenceReport(int $id, int $currentReportId): BugReport
    {
        if ($id <= 0 || $id === $currentReportId) {
            throw new \InvalidArgumentException('Sélectionnez un autre signalement de référence.');
        }

        $report = $this->reports->find($id);
        if (!$report instanceof BugReport) {
            throw new \RuntimeException('Signalement de référence introuvable.');
        }

        return $report;
    }

    private function statusLabel(string $status): string
    {
        return [
            'submitted' => 'Soumis',
            'under_review' => 'En cours d’analyse',
            'need_info' => 'Informations nécessaires',
            'planned' => 'Correction planifiée',
            'resolved' => 'Corrigé',
            'duplicate' => 'Doublon',
            'rejected' => 'Rejeté',
        ][$status] ?? $status;
    }
}
