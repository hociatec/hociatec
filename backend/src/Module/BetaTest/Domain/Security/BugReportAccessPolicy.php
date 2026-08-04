<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Security;

use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\User\Domain\Entity\User;

final readonly class BugReportAccessPolicy
{
    public function canView(User $user, BugReport $report): bool
    {
        return $this->isAdmin($user) || $report->getReporter()->getId() === $user->getId();
    }

    public function canComment(User $user, BugReport $report): bool
    {
        return $this->canView($user, $report);
    }

    public function canDownloadAttachment(User $user, BugReport $report): bool
    {
        return $this->canView($user, $report);
    }

    public function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }
}
