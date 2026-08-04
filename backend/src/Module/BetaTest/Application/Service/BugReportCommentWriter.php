<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Service;

use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
use App\Module\BetaTest\Domain\Exception\BetaTestOperationException;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class BugReportCommentWriter
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BugReportActivityLogger $activityLogger,
        private UserCommunicationNotifier $notifier,
        private UserRepositoryPort $users,
    ) {
    }

    public function create(BugReport $report, User $author, string $content): BugReportComment
    {
        $content = trim($content);
        if ('' === $content) {
            throw new \InvalidArgumentException('Le contenu du message ne peut pas être vide.');
        }

        $isAdmin = $author->isAdmin();
        $comment = new BugReportComment($report, $author, $content);
        $isAdmin ? $report->recordAdminReply() : $report->recordReporterReply();

        try {
            $this->persistence->persist($comment);
            $this->activityLogger->log($report, $author, 'comment_added', null, null, mb_substr($content, 0, 500));
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw BetaTestOperationException::failed('Impossible d’enregistrer le commentaire bêta.', $exception);
        }

        $this->notifyParticipants($report, $comment, $author, $isAdmin);

        return $comment;
    }

    private function notifyParticipants(BugReport $report, BugReportComment $comment, User $author, bool $isAdmin): void
    {
        if ($isAdmin && $report->getReporter()->getId() !== $author->getId()) {
            $this->notifier->notify(
                $report->getReporter(),
                sprintf('beta-report-comment:%d:%d', $report->getId(), $comment->getId()),
                'Nouveau message sur un signalement bêta',
                sprintf('Un nouveau message a été ajouté. Titre du signalement : %s.', $report->getTitle()),
                sprintf('/beta?reportId=%d', $report->getId()),
                'beta_report_comment',
            );

            return;
        }

        if ($isAdmin) {
            return;
        }

        foreach ($this->users->findAdmins() as $admin) {
            if ($admin->getId() === $author->getId()) {
                continue;
            }

            $this->notifier->notify(
                $admin,
                sprintf('admin-beta-report-comment:%d:%d:%d', $report->getId(), $comment->getId(), $admin->getId()),
                'Nouveau message client sur un signalement bêta',
                sprintf('%s a répondu. Titre du signalement : %s.', $author->getFullName(), $report->getTitle()),
                sprintf('/admin/beta-reports?reportId=%d', $report->getId()),
                'admin_beta_report_comment',
            );
        }
    }
}
