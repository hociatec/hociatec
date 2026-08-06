<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Application\Port\BetaAttachmentStoragePort;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteBugReportHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private BetaAttachmentStoragePort $attachments,
    ) {
    }

    public function delete(BugReport $report): void
    {
        $attachmentNames = $report->getAttachments();
        $this->persistence->remove($report);
        $this->persistence->commit();
        $this->attachments->deleteMany($attachmentNames);
    }
}
