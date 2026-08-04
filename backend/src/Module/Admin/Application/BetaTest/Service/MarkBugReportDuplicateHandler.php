<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Application\Service\BugReportActivityLogger;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;

final readonly class MarkBugReportDuplicateHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BugReportActivityLogger $activityLogger,
        private UserCommunicationNotifier $notifier,
    ) {
    }

    public function mark(BugReport $report, BugReport $duplicateOf, string $reason, ?User $actor): void
    {
        if ($duplicateOf->getId() === $report->getId()) {
            throw new \InvalidArgumentException('Sélectionnez un autre signalement de référence.');
        }

        $previous = $report->getDuplicateOf()?->getId();
        $report->markDuplicateOf($duplicateOf, $reason);
        $this->activityLogger->log($report, $actor, 'marked_duplicate', null !== $previous ? (string) $previous : null, (string) $duplicateOf->getId(), $reason);
        $this->persistence->commit();
        $this->notifier->notify(
            $report->getReporter(),
            sprintf('beta-report-duplicate:%d:%d', $report->getId(), $duplicateOf->getId()),
            'Signalement bêta marqué comme doublon',
            sprintf('Le signalement « %s » est rattaché au signalement « %s ».', $report->getTitle(), $duplicateOf->getTitle()),
            sprintf('/beta?reportId=%d', $report->getId()),
            'beta_report_status',
        );
    }
}
