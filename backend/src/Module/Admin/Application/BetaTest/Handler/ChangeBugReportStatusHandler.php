<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\Admin\Application\BetaTest\Provider\BugReportStatusLabelProvider;
use App\Module\BetaTest\Application\Workflow\BugReportActivityLogger;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class ChangeBugReportStatusHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BugReportActivityLogger $activityLogger,
        private UserCommunicationNotifier $notifier,
        private BugReportStatusLabelProvider $statusLabels,
    ) {
    }

    public function change(BugReport $report, string $status, ?User $actor): void
    {
        if (!in_array($status, BugReport::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException('État invalide.');
        }

        $previousStatus = $report->getStatus();
        $report->setStatus($status);
        if ($previousStatus !== $status) {
            $this->activityLogger->log($report, $actor, 'status_changed', $previousStatus, $status);
        }
        $this->persistence->commit();

        if ($previousStatus !== $status) {
            $changedAt = new \DateTimeImmutable();
            $this->notifier->notify(
                $report->getReporter(),
                sprintf('beta-report-status:%d:%s:%s', $report->getId(), $status, $changedAt->format('Uu')),
                'État d’un signalement bêta mis à jour',
                sprintf('Titre du signalement : %s. Nouvel état : %s.', $report->getTitle(), $this->statusLabels->label($status)),
                sprintf('/beta?reportId=%d', $report->getId()),
                'beta_report_status',
            );
        }
    }
}
