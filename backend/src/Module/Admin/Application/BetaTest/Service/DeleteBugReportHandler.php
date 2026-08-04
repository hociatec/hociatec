<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Application\Service\BetaAttachmentStorage;
use App\Module\BetaTest\Domain\Entity\BugReport;

final readonly class DeleteBugReportHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BetaAttachmentStorage $attachments,
    ) {
    }

    public function delete(BugReport $report): void
    {
        $attachmentNames = $report->getAttachments();
        $this->persistence->remove($report);
        $this->persistence->flush();
        $this->attachments->deleteMany($attachmentNames);
    }
}
