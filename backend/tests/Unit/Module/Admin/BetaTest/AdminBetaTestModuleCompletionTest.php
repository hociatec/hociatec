<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\BetaTest;

use App\Module\Admin\UI\BetaTest\Controller\AssignBugReportController;
use App\Module\Admin\UI\BetaTest\Controller\BugReportDashboardController;
use App\Module\Admin\UI\BetaTest\Controller\CreateCampaignController;
use App\Module\Admin\UI\BetaTest\Controller\DeleteBetaTesterController;
use App\Module\Admin\UI\BetaTest\Controller\DeleteBugReportController;
use App\Module\Admin\UI\BetaTest\Controller\DeleteCampaignController;
use App\Module\Admin\UI\BetaTest\Controller\ExportBetaTestersController;
use App\Module\Admin\UI\BetaTest\Controller\ExportBugReportsController;
use App\Module\Admin\UI\BetaTest\Controller\ListBetaTestersController;
use App\Module\Admin\UI\BetaTest\Controller\ListBugReportActivitiesController;
use App\Module\Admin\UI\BetaTest\Controller\ListBugReportsController;
use App\Module\Admin\UI\BetaTest\Controller\ListCampaignsController;
use App\Module\Admin\UI\BetaTest\Controller\MarkBugReportDuplicateController;
use App\Module\Admin\UI\BetaTest\Controller\UpdateBetaTesterController;
use App\Module\Admin\UI\BetaTest\Controller\UpdateBugReportStatusController;
use App\Module\Admin\UI\BetaTest\Controller\UpdateCampaignController;
use App\Module\Admin\Application\BetaTest\Service\AssignBugReportHandler;
use App\Module\Admin\Application\BetaTest\Service\BetaCampaignPayloadMapper;
use App\Module\Admin\Application\BetaTest\Service\BugReportReferenceProvider;
use App\Module\Admin\Application\BetaTest\Service\BugReportStatusLabelProvider;
use App\Module\Admin\Application\BetaTest\Service\ChangeBetaTesterStatusHandler;
use App\Module\Admin\Application\BetaTest\Service\ChangeBugReportStatusHandler;
use App\Module\Admin\Application\BetaTest\Service\CloseElapsedBetaCampaignsHandler;
use App\Module\Admin\Application\BetaTest\Service\CreateBetaCampaignHandler;
use App\Module\Admin\Application\BetaTest\Service\DeleteBetaCampaignHandler;
use App\Module\Admin\Application\BetaTest\Service\DeleteBetaTesterHandler;
use App\Module\Admin\Application\BetaTest\Service\DeleteBugReportHandler;
use App\Module\Admin\Application\BetaTest\Service\MarkBugReportDuplicateHandler;
use App\Module\Admin\Application\BetaTest\Service\UpdateBetaCampaignHandler;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
use App\Module\BetaTest\Infrastructure\Http\BugReportResponseFormatter;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportActivityRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\BetaTest\Application\Storage\BetaAttachmentStorage;
use App\Module\BetaTest\Application\Workflow\BugReportActivityLogger;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validation;

final class AdminBetaTestModuleCompletionTest extends TestCase
{
    public function testAdminBetaControllersCoverCampaignsProfilesReportsExportsAndActivity(): void
    {
        $em = $this->entityManager();
        [$reporter, $admin, $campaign, $report, $duplicate] = $this->seed($em);
        $persistence = new DoctrineUnitOfWork($em);
        $formatter = new BugReportResponseFormatter();
        $activity = new BugReportActivityLogger($persistence);
        $notifier = $this->notifier($em);
        $storage = new BetaAttachmentStorage($this->projectDir(), $this->createMock(LoggerInterface::class));
        $accessPolicy = new BugReportAccessPolicy();
        $campaignPayloads = new BetaCampaignPayloadMapper();
        $createCampaignHandler = new CreateBetaCampaignHandler($persistence, $campaignPayloads);
        $updateCampaignHandler = new UpdateBetaCampaignHandler($persistence, $campaignPayloads);
        $deleteCampaignHandler = new DeleteBetaCampaignHandler($persistence);
        $closeElapsedCampaigns = new CloseElapsedBetaCampaignsHandler($persistence);
        $changeTesterStatus = new ChangeBetaTesterStatusHandler($persistence);
        $deleteTesterHandler = new DeleteBetaTesterHandler($persistence);
        $assignBugReport = new AssignBugReportHandler($persistence, $activity);
        $changeBugReportStatus = new ChangeBugReportStatusHandler($persistence, $activity, $notifier, new BugReportStatusLabelProvider());
        $bugReportReferences = new BugReportReferenceProvider($this->reports($em));
        $markBugReportDuplicate = new MarkBugReportDuplicateHandler($persistence, $activity, $notifier);
        $deleteBugReport = new DeleteBugReportHandler($persistence, $storage);

        $validator = $this->validator();
        $createCampaign = new CreateCampaignController($createCampaignHandler, $validator);
        self::assertSame(422, $createCampaign($this->jsonRequest(['name' => '', 'description' => 'Desc']))->getStatusCode());
        self::assertSame(422, $createCampaign($this->jsonRequest(['name' => 'Bad dates', 'description' => 'Desc', 'startsAt' => '2026-08-10', 'endsAt' => '2026-08-01']))->getStatusCode());
        $createdCampaign = $createCampaign($this->jsonRequest(['name' => 'Created', 'description' => 'Desc', 'startsAt' => 'bad', 'status' => 'weird']));
        self::assertSame(201, $createdCampaign->getStatusCode());
        $createdCampaignId = (int) json_decode((string) $createdCampaign->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $listCampaigns = new ListCampaignsController($this->campaigns($em), $this->profiles($em), $this->reports($em), $formatter, $closeElapsedCampaigns);
        self::assertSame(200, $listCampaigns()->getStatusCode());

        $updateCampaign = new UpdateCampaignController($this->campaigns($em), $updateCampaignHandler, $validator);
        self::assertSame(404, $updateCampaign(999, $this->jsonRequest(['name' => 'Nope'], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['name' => ''], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['description' => ''], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['startsAt' => '2026-08-10', 'endsAt' => '2026-08-01'], 'PATCH'))->getStatusCode());
        self::assertSame(200, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['name' => 'Updated', 'description' => 'Updated desc', 'status' => 'active', 'startsAt' => '', 'endsAt' => ''], 'PATCH'))->getStatusCode());

        $deleteCampaign = new DeleteCampaignController($this->campaigns($em), $deleteCampaignHandler);
        self::assertSame(404, $deleteCampaign(999)->getStatusCode());
        self::assertSame(200, $deleteCampaign($createdCampaignId)->getStatusCode());

        $listTesters = new ListBetaTestersController($this->profiles($em));
        self::assertSame(200, $listTesters(Request::create('/?search=reporter&status=accepted&accessibility=none'))->getStatusCode());
        $testersExport = (new ExportBetaTestersController($this->profiles($em), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory()))();
        self::assertSame(200, $testersExport->getStatusCode());
        self::assertStringContainsString('beta-testeurs.csv', (string) $testersExport->headers->get('Content-Disposition'));

        $profile = $this->profiles($em)->findOneByUser($reporter);
        self::assertInstanceOf(BetaTesterProfile::class, $profile);
        $deleteProfileUser = $this->user('delete-profile-admin-beta@example.test');
        $deleteProfile = (new BetaTesterProfile($deleteProfileUser, ['weekdays'], 'Delete', 'manual', 'clear', 'advanced', 'none', ['nvda'], ['windows'], ['chrome'], ['ui'], new \DateTimeImmutable(), '2026-07-27'))->setStatus(BetaTesterProfile::STATUS_PENDING);
        $em->persist($deleteProfileUser);
        $em->persist($deleteProfile);
        $em->flush();
        $updateTester = new UpdateBetaTesterController($this->profiles($em), $changeTesterStatus);
        self::assertSame(404, $updateTester(999, $this->jsonRequest(['status' => 'accepted'], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateTester((int) $profile->getId(), $this->jsonRequest(['status' => 'bad'], 'PATCH'))->getStatusCode());
        self::assertSame(200, $updateTester((int) $profile->getId(), $this->jsonRequest(['status' => 'paused'], 'PATCH'))->getStatusCode());

        $reports = $this->reports($em);
        self::assertSame(200, (new BugReportDashboardController($reports, $this->campaigns($em), $this->users($em)))()->getStatusCode());
        self::assertSame(200, (new ListBugReportsController($reports, $formatter))(Request::create('/?status=submitted&severity=high&search=bug&campaignId='.$campaign->getId().'&assignedTo='.$admin->getId()))->getStatusCode());
        $export = (new ExportBugReportsController($reports, new \App\Shared\Infrastructure\Http\AttachmentResponseFactory()))(Request::create('/?status=submitted'));
        self::assertInstanceOf(StreamedResponse::class, $export);
        self::assertSame(200, $export->getStatusCode());
        ob_start();
        $export->sendContent();
        $csv = (string) ob_get_clean();
        self::assertStringContainsString('Titre', $csv);

        $assign = new AssignBugReportController($reports, $this->users($em), $assignBugReport);
        $assign->setContainer($this->container($admin));
        self::assertSame(404, $assign(999, $this->jsonRequest(['assignedToId' => $admin->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(404, $assign((int) $report->getId(), $this->jsonRequest(['assignedToId' => $reporter->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(200, $assign((int) $report->getId(), $this->jsonRequest(['assignedToId' => $admin->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(200, $assign((int) $report->getId(), $this->jsonRequest(['assignedToId' => ''], 'PATCH'))->getStatusCode());

        $status = new UpdateBugReportStatusController($reports, $changeBugReportStatus);
        $status->setContainer($this->container($admin));
        self::assertSame(404, $status(999, $this->jsonRequest(['status' => BugReport::STATUS_RESOLVED], 'PATCH'))->getStatusCode());
        self::assertSame(422, $status((int) $report->getId(), $this->jsonRequest(['status' => 'bad'], 'PATCH'))->getStatusCode());
        self::assertSame(200, $status((int) $report->getId(), $this->jsonRequest(['status' => BugReport::STATUS_SUBMITTED], 'PATCH'))->getStatusCode());
        self::assertSame(200, $status((int) $report->getId(), $this->jsonRequest(['status' => BugReport::STATUS_RESOLVED], 'PATCH'))->getStatusCode());

        $markDuplicate = new MarkBugReportDuplicateController($reports, $bugReportReferences, $markBugReportDuplicate);
        $markDuplicate->setContainer($this->container($admin));
        self::assertSame(404, $markDuplicate(999, $this->jsonRequest(['duplicateOfId' => $duplicate->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(422, $markDuplicate((int) $report->getId(), $this->jsonRequest(['duplicateOfId' => $report->getId()], 'PATCH'))->getStatusCode());
        self::assertSame(404, $markDuplicate((int) $report->getId(), $this->jsonRequest(['duplicateOfId' => 999], 'PATCH'))->getStatusCode());
        self::assertSame(200, $markDuplicate((int) $report->getId(), $this->jsonRequest(['duplicateOfId' => $duplicate->getId(), 'reason' => 'same'], 'PATCH'))->getStatusCode());

        $activities = new ListBugReportActivitiesController($reports, new BugReportActivityRepository($this->registry($em)));
        self::assertSame(404, $activities(999)->getStatusCode());
        self::assertSame(200, $activities((int) $report->getId())->getStatusCode());

        $deleteTester = new DeleteBetaTesterController($this->profiles($em), $deleteTesterHandler);
        self::assertSame(404, $deleteTester(999)->getStatusCode());
        self::assertSame(200, $deleteTester((int) $deleteProfile->getId())->getStatusCode());

        file_put_contents($this->projectDir().'/var/beta-attachments/screen.png', 'image');
        $deleteReport = new DeleteBugReportController($reports, $deleteBugReport);
        self::assertSame(404, $deleteReport(999)->getStatusCode());
        self::assertSame(200, $deleteReport((int) $report->getId())->getStatusCode());
    }

    /** @return array{User, User, BetaCampaign, BugReport, BugReport} */
    private function seed(EntityManager $em): array
    {
        $reporter = $this->user('reporter-admin-beta@example.test');
        $admin = $this->user('admin-beta@example.test', ['ROLE_ADMIN']);
        $campaign = (new BetaCampaign('Campaign', 'Desc', new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('+1 day')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $closedCampaign = (new BetaCampaign('Closed', 'Desc', new \DateTimeImmutable('-3 days'), new \DateTimeImmutable('-1 day')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $profile = (new BetaTesterProfile($reporter, ['weekdays'], 'Motivation', 'manual', 'clear', 'advanced', 'none', ['nvda'], ['windows'], ['chrome'], ['ui'], new \DateTimeImmutable(), '2026-07-26'))->setStatus(BetaTesterProfile::STATUS_ACCEPTED);
        $report = new BugReport($reporter, $campaign, 'Bug', 'Desc', 'Expected', 'Actual', 'high', '/page', ['screen.png']);
        $duplicate = new BugReport($reporter, $campaign, 'Bug duplicate', 'Desc', null, null, 'normal', null, []);
        foreach ([$reporter, $admin, $campaign, $closedCampaign, $profile, $report, $duplicate, new BugReportComment($report, $reporter, 'Hello'), new BugReportActivity($report, $admin, 'created', null, null, 'Created')] as $entity) {
            $em->persist($entity);
        }
        $em->flush();

        return [$reporter, $admin, $campaign, $report, $duplicate];
    }

    /** @param list<string> $roles */
    private function user(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed')->setRoles($roles);

        return $user;
    }

    private function notifier(EntityManager $em): UserCommunicationNotifier
    {
        return new UserCommunicationNotifier(
            new AccountNotificationEventRepository($this->registry($em)),
            new DoctrineUnitOfWork($em),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(BetaCampaign::class),
            $em->getClassMetadata(BetaTesterProfile::class),
            $em->getClassMetadata(BugReport::class),
            $em->getClassMetadata(BugReportComment::class),
            $em->getClassMetadata(BugReportActivity::class),
            $em->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $em;
    }

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function campaigns(EntityManager $em): BetaCampaignRepository
    {
        return new BetaCampaignRepository($this->registry($em));
    }

    private function profiles(EntityManager $em): BetaTesterProfileRepository
    {
        return new BetaTesterProfileRepository($this->registry($em));
    }

    private function reports(EntityManager $em): BugReportRepository
    {
        return new BugReportRepository($this->registry($em));
    }

    private function users(EntityManager $em): UserRepository
    {
        return new UserRepository($this->registry($em));
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function container(User $user): Container
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    private function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-admin-beta-tests';
        if (!is_dir($dir.'/var/beta-attachments')) {
            mkdir($dir.'/var/beta-attachments', 0777, true);
        }

        return $dir;
    }
}
