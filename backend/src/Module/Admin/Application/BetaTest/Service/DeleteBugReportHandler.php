<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Module\BetaTest\Application\Storage\BetaAttachmentStorage;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

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
        $this->persistence->commit();
        $this->attachments->deleteMany($attachmentNames);
    }
}
