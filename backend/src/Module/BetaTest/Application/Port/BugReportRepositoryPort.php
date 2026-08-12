<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Port;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;

interface BugReportRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BugReport;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<BugReport>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @return list<BugReport> */
    public function findForUser(User $user): array;

    /** @return list<BugReport> */
    public function findForUserPaginated(User $user, int $limit, int $offset): array;

    public function countForUser(User $user): int;

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<BugReport>
     */
    public function findForAdmin(array $filters, int $limit, int $offset): array;

    /** @param array<string, mixed> $filters */
    public function countForAdmin(array $filters): int;

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<BugReport>
     */
    public function findExportRows(array $filters): array;

    /** @return array<string, mixed> */
    public function dashboardStats(): array;

    /** @return array{openReports:int,resolvedReports:int,totalReports:int} */
    public function dashboardStatsForUser(User $user): array;

    public function countOpenForCampaign(BetaCampaign $campaign): int;
}
