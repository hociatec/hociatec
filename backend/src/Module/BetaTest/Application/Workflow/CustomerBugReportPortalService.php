<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Workflow;

use App\Module\BetaTest\Application\Port\BetaAttachmentStoragePort;
use App\Module\BetaTest\Application\Port\BugReportCommentRepositoryPort;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\BetaTest\Application\Writer\BugReportCommentWriter;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
use App\Module\BetaTest\Domain\Exception\BetaTestOperationException;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerBugReportPortalService
{
    public function __construct(
        private BugReportRepositoryPort $reports,
        private BugReportAccessPolicy $accessPolicy,
        private ?BugReportCommentRepositoryPort $comments = null,
        private ?BugReportCommentWriter $commentWriter = null,
        private ?BetaAttachmentStoragePort $attachments = null,
    ) {
    }

    /** @return array{items:list<BugReport>, total:int} */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        /** @var list<BugReport> $reports */
        $reports = $this->reports->findForUserPaginated($user, $limit, $offset);

        return [
            'items' => $reports,
            'total' => $this->reports->countForUser($user),
        ];
    }

    public function showForUser(User $user, int $reportId): ?BugReport
    {
        return $this->findAccessibleReport($user, $reportId, 'view');
    }

    /**
     * @return array{items:list<BugReportComment>, total:int}|null
     */
    public function listCommentsForUser(User $user, int $reportId, int $limit, int $offset): ?array
    {
        if (!$this->comments instanceof BugReportCommentRepositoryPort) {
            throw new \LogicException('Bug report comment dependencies are not configured.');
        }

        $report = $this->findAccessibleReport($user, $reportId, 'view');
        if (!$report instanceof BugReport) {
            return null;
        }

        $comments = $this->comments->findForReportPaginated($report, $limit, $offset);

        return [
            'items' => $comments,
            'total' => $this->comments->countForReport($report),
        ];
    }

    /**
     * @throws BetaTestOperationException
     */
    public function createCommentForUser(User $user, int $reportId, string $content): ?BugReportComment
    {
        if (!$this->commentWriter instanceof BugReportCommentWriter) {
            throw new \LogicException('Bug report comment writer dependencies are not configured.');
        }

        $report = $this->findAccessibleReport($user, $reportId, 'comment');
        if (!$report instanceof BugReport) {
            return null;
        }

        return $this->commentWriter->create($report, $user, $content);
    }

    public function attachmentPathForUser(User $user, int $reportId, string $name): ?string
    {
        if (!$this->attachments instanceof BetaAttachmentStoragePort) {
            throw new \LogicException('Bug report attachment storage is not configured.');
        }

        $report = $this->findAccessibleReport($user, $reportId, 'download');
        if (!$report instanceof BugReport) {
            return null;
        }

        if (!in_array($name, $report->getAttachments(), true)) {
            return null;
        }

        return $this->attachments->path($name);
    }

    private function findAccessibleReport(User $user, int $reportId, string $ability): ?BugReport
    {
        $report = $this->reports->find($reportId);
        if (!$report instanceof BugReport) {
            return null;
        }

        if ($user->isAdmin()) {
            return $report;
        }

        $allowed = match ($ability) {
            'comment' => $this->accessPolicy->canComment($user, $report),
            'download' => $this->accessPolicy->canDownloadAttachment($user, $report),
            default => $this->accessPolicy->canView($user, $report),
        };
        if (!$allowed) {
            throw new \DomainException('Accès refusé.');
        }

        return $report;
    }
}
