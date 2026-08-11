<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\BetaTest;

use App\Module\Admin\UI\BetaTest\Controller\AssignBugReportController;
use App\Module\Admin\UI\BetaTest\Controller\BugReportDashboardController;
use App\Module\Admin\UI\BetaTest\Controller\DeleteBugReportController;
use App\Module\Admin\UI\BetaTest\Controller\ExportBugReportsController;
use App\Module\Admin\UI\BetaTest\Controller\ListBugReportActivitiesController;
use App\Module\Admin\UI\BetaTest\Controller\ListBugReportsController;
use App\Module\Admin\UI\BetaTest\Controller\MarkBugReportDuplicateController;
use App\Module\Admin\UI\BetaTest\Controller\UpdateBugReportStatusController;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Infrastructure\Repository\BugReportActivityRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminBugReportControllersTest extends AdminBetaTestIntegrationTestCase
{
    public function testBugReportControllers(): void
    {
        $em = $this->entityManager();
        [$reporter, $admin, $campaign, $report, $duplicate] = $this->seed($em);
        $persistence = new DoctrineUnitOfWork($em);
        $activity = $this->activityLogger($persistence);
        $notifier = $this->notifier($em);
        $reports = $this->reports($em);

        self::assertSame(200, (new BugReportDashboardController($reports, $this->campaigns($em), $this->users($em)))()->getStatusCode());
        self::assertSame(200, (new ListBugReportsController($reports, $this->formatter()))(Request::create('/?status=submitted&severity=high&search=bug&campaignId='.$campaign->getId().'&assignedTo='.$admin->getId()))->getStatusCode());
        $export = (new ExportBugReportsController($reports, new \App\Shared\Infrastructure\Http\AttachmentResponseFactory()))(Request::create('/?status=submitted'));
        self::assertInstanceOf(StreamedResponse::class, $export);
        self::assertSame(200, $export->getStatusCode());
        ob_start();
        $export->sendContent();
        $csv = (string) ob_get_clean();
        self::assertStringContainsString('Titre', $csv);

        $assign = new AssignBugReportController($reports, $this->users($em), $this->assignBugReportHandler($persistence, $activity));
        $assign->setContainer($this->container($admin));
        self::assertSame(404, $assign(999, $this->jsonRequest(['assignedToId' => $admin->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(404, $assign((int) $report->getId(), $this->jsonRequest(['assignedToId' => $reporter->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(200, $assign((int) $report->getId(), $this->jsonRequest(['assignedToId' => $admin->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(200, $assign((int) $report->getId(), $this->jsonRequest(['assignedToId' => ''], 'PATCH'))->getStatusCode());

        $status = new UpdateBugReportStatusController($reports, $this->changeBugReportStatusHandler($persistence, $activity, $notifier));
        $status->setContainer($this->container($admin));
        self::assertSame(404, $status(999, $this->jsonRequest(['status' => BugReport::STATUS_RESOLVED], 'PATCH'))->getStatusCode());
        self::assertSame(422, $status((int) $report->getId(), $this->jsonRequest(['status' => 'bad'], 'PATCH'))->getStatusCode());
        self::assertSame(200, $status((int) $report->getId(), $this->jsonRequest(['status' => BugReport::STATUS_SUBMITTED], 'PATCH'))->getStatusCode());
        self::assertSame(200, $status((int) $report->getId(), $this->jsonRequest(['status' => BugReport::STATUS_RESOLVED], 'PATCH'))->getStatusCode());

        $markDuplicate = new MarkBugReportDuplicateController($reports, $this->bugReportReferenceProvider($em), $this->markBugReportDuplicateHandler($persistence, $activity, $notifier));
        $markDuplicate->setContainer($this->container($admin));
        self::assertSame(404, $markDuplicate(999, $this->jsonRequest(['duplicateOfId' => $duplicate->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(422, $markDuplicate((int) $report->getId(), $this->jsonRequest(['duplicateOfId' => $report->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(404, $markDuplicate((int) $report->getId(), $this->jsonRequest(['duplicateOfId' => 999], 'PATCH'))->getStatusCode());
        self::assertSame(200, $markDuplicate((int) $report->getId(), $this->jsonRequest(['duplicateOfId' => $duplicate->getId(), 'reason' => 'same'], 'PATCH'))->getStatusCode());

        $activities = new ListBugReportActivitiesController($reports, new BugReportActivityRepository($this->registry($em)));
        self::assertSame(404, $activities(999, new Request())->getStatusCode());
        self::assertSame(200, $activities((int) $report->getId(), new Request())->getStatusCode());

        file_put_contents($this->projectDir().'/var/beta-attachments/screen.png', 'image');
        $deleteReport = new DeleteBugReportController($reports, $this->deleteBugReportHandler($persistence));
        self::assertSame(404, $deleteReport(999)->getStatusCode());
        self::assertSame(200, $deleteReport((int) $report->getId())->getStatusCode());
    }
}
