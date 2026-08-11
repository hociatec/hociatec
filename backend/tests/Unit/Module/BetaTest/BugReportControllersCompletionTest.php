<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\BetaTest;

use App\Module\BetaTest\Application\Workflow\CustomerBugReportPortalService;
use App\Module\BetaTest\Application\Workflow\BugReportActivityLogger;
use App\Module\BetaTest\Application\Writer\BugReportCommentWriter;
use App\Module\BetaTest\Application\Writer\BugReportWriter;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\BetaTest\Infrastructure\Storage\BetaAttachmentStorage;
use App\Module\BetaTest\UI\Controller\CreateBugReportCommentController;
use App\Module\BetaTest\UI\Controller\CreateBugReportController;
use App\Module\BetaTest\UI\Controller\DownloadBugReportAttachmentController;
use App\Module\BetaTest\UI\Controller\ListBugReportCommentsController;
use App\Module\BetaTest\UI\Controller\ListMyBugReportsController;
use App\Module\BetaTest\UI\Controller\ShowBugReportController;
use App\Module\BetaTest\UI\Http\BugReportCommentFormatter;
use App\Module\BetaTest\Application\Projection\BugReportResponseFormatter;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BugReportControllersCompletionTest extends BetaTestIntegrationTestCase
{
    public function testBugReportControllersRepositoriesFormatterAndAttachments(): void
    {
        $em = $this->entityManager();
        [$user, $admin, $campaign, $report] = $this->persistBugGraph($em);
        $storage = new BetaAttachmentStorage($this->projectDir(), $this->createMock(LoggerInterface::class));
        file_put_contents($this->projectDir().'/var/beta-attachments/screen.png', 'image');

        $reports = $this->reports($em);
        $comments = $this->comments($em);
        $formatter = new BugReportResponseFormatter();
        $accessPolicy = new BugReportAccessPolicy();
        $commentFormatter = new BugReportCommentFormatter();
        $persistence = new DoctrineUnitOfWork($em);
        $notifier = $this->notifier($em);
        $activity = new BugReportActivityLogger($persistence);
        $portal = new CustomerBugReportPortalService(
            $reports,
            $accessPolicy,
            $comments,
            new BugReportCommentWriter($persistence, $activity, $notifier, $this->users($em)),
            $storage,
        );

        self::assertCount(1, $reports->findForUser($user));
        self::assertCount(1, $reports->findForUserPaginated($user, 10, 0));
        self::assertSame(1, $reports->countForUser($user));
        self::assertSame(1, $reports->countOpenForCampaign($campaign));
        self::assertArrayHasKey('openReports', $reports->dashboardStats());
        self::assertCount(1, $reports->findForAdmin(['search' => 'Bug', 'status' => \App\Module\BetaTest\Domain\Entity\BugReport::STATUS_SUBMITTED], 10, 0));
        self::assertSame(1, $reports->countForAdmin(['severity' => 'high']));
        self::assertCount(1, $reports->findExportRows(['campaignId' => $campaign->getId()]));

        $list = new ListMyBugReportsController($portal, $formatter);
        $list->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $list(Request::create('/'))->getStatusCode());
        $list->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $list(Request::create('/?page=1&perPage=5'))->getStatusCode());

        $show = new ShowBugReportController($portal, $formatter);
        $show->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $show((int) $report->getId())->getStatusCode());
        $show->setContainer($this->container($user));
        self::assertSame(Response::HTTP_NOT_FOUND, $show(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $show((int) $report->getId())->getStatusCode());
        $show->setContainer($this->container($this->user('other@example.com')));
        self::assertSame(Response::HTTP_FORBIDDEN, $show((int) $report->getId())->getStatusCode());

        $commentList = new ListBugReportCommentsController($portal, $commentFormatter);
        $commentList->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $commentList((int) $report->getId(), Request::create('/'))->getStatusCode());
        $commentList->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $commentList((int) $report->getId(), Request::create('/'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $commentList(999, Request::create('/'))->getStatusCode());
        $commentList->setContainer($this->container($this->user('comment-other@example.com')));
        self::assertSame(Response::HTTP_FORBIDDEN, $commentList((int) $report->getId(), Request::create('/'))->getStatusCode());

        $createComment = new CreateBugReportCommentController($portal, $commentFormatter);
        $createComment->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $createComment((int) $report->getId(), Request::create('/', 'POST', [], [], [], [], '{"content":"No auth"}'))->getStatusCode());
        $createComment->setContainer($this->container($user));
        self::assertSame(Response::HTTP_NOT_FOUND, $createComment(999, Request::create('/', 'POST', [], [], [], [], '{"content":"Missing"}'))->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $createComment((int) $report->getId(), Request::create('/', 'POST', [], [], [], [], '{"content":""}'))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $createComment((int) $report->getId(), Request::create('/', 'POST', [], [], [], [], '{"content":"Merci"}'))->getStatusCode());
        $createComment->setContainer($this->container($this->user('comment-forbidden@example.com')));
        self::assertSame(Response::HTTP_FORBIDDEN, $createComment((int) $report->getId(), Request::create('/', 'POST', [], [], [], [], '{"content":"Forbidden"}'))->getStatusCode());
        $createComment->setContainer($this->container($admin));
        self::assertSame(Response::HTTP_CREATED, $createComment((int) $report->getId(), Request::create('/', 'POST', [], [], [], [], '{"content":"Admin"}'))->getStatusCode());

        $download = new DownloadBugReportAttachmentController($portal);
        $download->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $download((int) $report->getId(), 'screen.png')->getStatusCode());
        $download->setContainer($this->container($user));
        self::assertSame(Response::HTTP_NOT_FOUND, $download(999, 'screen.png')->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $download((int) $report->getId(), 'missing.png')->getStatusCode());
        $download->setContainer($this->container($this->user('download-other@example.com')));
        self::assertSame(Response::HTTP_FORBIDDEN, $download((int) $report->getId(), 'screen.png')->getStatusCode());
        $download->setContainer($this->container($admin));
        self::assertSame(Response::HTTP_NOT_FOUND, $download((int) $report->getId(), 'ghost.png')->getStatusCode());
        self::assertSame(Response::HTTP_OK, $download((int) $report->getId(), 'screen.png')->getStatusCode());

        $reportWriter = new BugReportWriter($persistence, $storage, $activity, $this->users($em), $notifier);
        $create = new CreateBugReportController($this->campaigns($em), $this->profiles($em), $reportWriter);
        $create->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $create(Request::create('/', 'POST', [], [], [], [], '{"title":"Bug","description":"Desc"}'))->getStatusCode());
        $create->setContainer($this->container($this->user('no-profile@example.com')));
        self::assertSame(Response::HTTP_FORBIDDEN, $create(Request::create('/', 'POST', [], [], [], [], '{"title":"Bug","description":"Desc"}'))->getStatusCode());
        $create->setContainer($this->container($user));
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $create(Request::create('/', 'POST', [], [], [], [], '{"title":"","description":""}'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $create(Request::create('/', 'POST', [], [], [], [], '{"title":"Bug","description":"Desc","campaignId":999}'))->getStatusCode());
        $closed = (new BetaCampaign('Closed reports', 'Desc', new \DateTimeImmutable('-2 days'), new \DateTimeImmutable('-1 day')))->setStatus(BetaCampaign::STATUS_CLOSED);
        $em->persist($closed);
        $em->flush();
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $create(Request::create('/', 'POST', [], [], [], [], json_encode(['title' => 'Bug', 'description' => 'Desc', 'campaignId' => $closed->getId()], JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $create(Request::create('/', 'POST', [], [], [], [], json_encode(['title' => 'Bug 2', 'description' => 'Desc', 'campaignId' => $campaign->getId(), 'severity' => 'bogus'], JSON_THROW_ON_ERROR)))->getStatusCode());

        $path = tempnam(sys_get_temp_dir(), 'beta');
        self::assertIsString($path);
        file_put_contents($path, "\x89PNG\r\n\x1A\n");
        $stored = $storage->store([new class($path) extends UploadedFile {
            public function __construct(string $path)
            {
                parent::__construct($path, 'screen.png', 'image/png', null, true);
            }

            public function getMimeType(): ?string
            {
                return 'image/png';
            }

            public function guessExtension(): ?string
            {
                return 'png';
            }
        }, 'bad']);
        self::assertCount(1, $stored);
        self::assertNotNull($storage->path($stored[0]));
        self::assertNull($storage->path(''));
        self::assertNull($storage->path('../bad'));
        $storage->deleteMany([$stored[0], 123]);
        self::assertNull($storage->path($stored[0]));
    }
}
