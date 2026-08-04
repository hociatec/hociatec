<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Service;

use App\Module\BetaTest\Entity\BetaCampaign;
use App\Module\BetaTest\Entity\BugReport;
use App\Module\BetaTest\Exception\BetaTestOperationException;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class BugReportWriter
{
    public function __construct(
        private DoctrinePersistence $persistence,
        private BetaAttachmentStorage $attachments,
        private BugReportActivityLogger $activityLogger,
        private UserRepository $users,
        private UserCommunicationNotifier $notifier,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<UploadedFile> $files
     */
    public function create(User $user, ?BetaCampaign $campaign, array $payload, array $files): BugReport
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        if ('' === $title || '' === $description) {
            throw new \InvalidArgumentException('Le titre et la description sont obligatoires.');
        }

        $report = new BugReport(
            $user,
            $campaign,
            $title,
            $description,
            isset($payload['expectedBehavior']) ? (string) $payload['expectedBehavior'] : null,
            isset($payload['actualBehavior']) ? (string) $payload['actualBehavior'] : null,
            in_array($payload['severity'] ?? 'normal', ['low', 'normal', 'high', 'critical'], true) ? (string) $payload['severity'] : 'normal',
            isset($payload['pageUrl']) ? (string) $payload['pageUrl'] : null,
            $this->attachments->store($files),
        );
        $report->recordReporterReply();

        try {
            $this->persistence->persist($report);
            $this->activityLogger->log($report, $user, 'report_created', null, null, $title);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw BetaTestOperationException::failed('Impossible d’enregistrer le signalement bêta.', $exception);
        }

        foreach ($this->users->findAdmins() as $admin) {
            $this->notifier->notify(
                $admin,
                sprintf('admin-beta-report-created:%d:%d', $report->getId(), $admin->getId()),
                'Nouveau signalement bêta',
                sprintf('%s a envoyé un signalement. Titre : %s.', $user->getFullName(), $report->getTitle()),
                sprintf('/admin/beta-reports?reportId=%d', $report->getId()),
                'admin_beta_report_created',
            );
        }

        return $report;
    }
}
