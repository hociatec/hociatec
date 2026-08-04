<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\BetaTest;

use App\Module\BetaTest\UI\Controller\CreateBugReportCommentController;
use App\Module\BetaTest\UI\Controller\CreateBugReportController;
use App\Module\BetaTest\UI\Controller\DownloadBugReportAttachmentController;
use App\Module\BetaTest\UI\Controller\GetMyBetaProfileController;
use App\Module\BetaTest\UI\Controller\LeaveBetaProgramController;
use App\Module\BetaTest\UI\Controller\ListBetaCampaignsController;
use App\Module\BetaTest\UI\Controller\ListBugReportCommentsController;
use App\Module\BetaTest\UI\Controller\ListMyBugReportsController;
use App\Module\BetaTest\UI\Controller\ShowBugReportController;
use App\Module\BetaTest\UI\Controller\UpdateMyBetaProfileController;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
use App\Module\BetaTest\Infrastructure\Http\BugReportCommentFormatter;
use App\Module\BetaTest\Infrastructure\Http\BugReportResponseFormatter;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportCommentRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\BetaTest\Application\Service\BetaAttachmentStorage;
use App\Module\BetaTest\Application\Service\BetaCampaignProvider;
use App\Module\BetaTest\Application\Service\BetaTesterProfileService;
use App\Module\BetaTest\Application\Service\BugReportActivityLogger;
use App\Module\BetaTest\Application\Service\BugReportCommentWriter;
use App\Module\BetaTest\Application\Service\BugReportWriter;
use App\Module\BetaTest\Infrastructure\Http\BetaCampaignResponseFormatter;
use App\Module\BetaTest\Infrastructure\Http\BetaProfileResponseFormatter;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BetaTestModuleCompletionTest extends TestCase
{
    public function testProfileAndCampaignControllersCoverMainBranches(): void
    {
        $em = $this->entityManager();
        $user = $this->user('beta@example.com');
        $em->persist($user);
        $em->flush();

        $profiles = $this->profiles($em);
        $campaigns = $this->campaigns($em);
        $persistence = new DoctrineUnitOfWork($em);

        $profileService = new BetaTesterProfileService($persistence);
        $list = new ListBetaCampaignsController($profiles, new BetaCampaignProvider($campaigns, $persistence), new BetaCampaignResponseFormatter());
        $list->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $list()->getStatusCode());
        $list->setContainer($this->container($user));
        self::assertSame([], json_decode((string) $list()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']['items']);

        $get = new GetMyBetaProfileController($profiles, new BetaProfileResponseFormatter());
        $get->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $get()->getStatusCode());
        $get->setContainer($this->container($user));
        self::assertSame(Response::HTTP_NOT_FOUND, $get()->getStatusCode());

        $update = new UpdateMyBetaProfileController($profiles, $this->validator(2), $profileService);
        $update->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $update(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        $update->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $update(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        $profile = $profiles->findOneByUser($user);
        self::assertInstanceOf(BetaTesterProfile::class, $profile);
        $profile->setStatus(BetaTesterProfile::STATUS_ACCEPTED);

        $active = (new BetaCampaign('Active', 'Desc', new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('+1 day')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $closed = (new BetaCampaign('Closed', 'Desc', new \DateTimeImmutable('-3 days'), new \DateTimeImmutable('-1 day')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $em->persist($active);
        $em->persist($closed);
        $em->flush();

        self::assertSame(Response::HTTP_OK, $get()->getStatusCode());
        $payload = json_decode((string) $list()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $payload['data']['items']);
        self::assertSame(BetaCampaign::STATUS_CLOSED, $closed->getStatus());

        self::assertSame(Response::HTTP_OK, $update(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(['motivation' => 'Updated']), JSON_THROW_ON_ERROR)))->getStatusCode());

        $leave = new LeaveBetaProgramController($profiles, $profileService);
        $leave->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $leave()->getStatusCode());
        $leave->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $leave()->getStatusCode());
        self::assertNull($profiles->findOneByUser($user));
        self::assertSame(Response::HTTP_OK, $leave()->getStatusCode());
    }

    public function testBugReportControllersRepositoriesFormatterAndAttachments(): void
    {
        $em = $this->entityManager();
        [$user, $admin, $campaign, $report] = $this->persistBugGraph($em);
        $storage = new BetaAttachmentStorage($this->projectDir(), $this->createMock(\Psr\Log\LoggerInterface::class));
        file_put_contents($this->projectDir().'/var/beta-attachments/screen.png', 'image');

        $reports = $this->reports($em);
        $comments = $this->comments($em);
        $formatter = new BugReportResponseFormatter();
        $accessPolicy = new BugReportAccessPolicy();
        $commentFormatter = new BugReportCommentFormatter();
        $persistence = new DoctrineUnitOfWork($em);
        $notifier = $this->notifier($em);
        $activity = new BugReportActivityLogger($persistence);

        self::assertCount(1, $reports->findForUser($user));
        self::assertCount(1, $reports->findForUserPaginated($user, 10, 0));
        self::assertSame(1, $reports->countForUser($user));
        self::assertSame(1, $reports->countOpenForCampaign($campaign));
        self::assertArrayHasKey('openReports', $reports->dashboardStats());
        self::assertCount(1, $reports->findForAdmin(['search' => 'Bug', 'status' => BugReport::STATUS_SUBMITTED], 10, 0));
        self::assertSame(1, $reports->countForAdmin(['severity' => 'high']));
        self::assertCount(1, $reports->findExportRows(['campaignId' => $campaign->getId()]));

        $list = new ListMyBugReportsController($reports, $formatter);
        $list->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $list(Request::create('/'))->getStatusCode());
        $list->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $list(Request::create('/?page=1&perPage=5'))->getStatusCode());

        $show = new ShowBugReportController($reports, $formatter, $accessPolicy);
        $show->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $show((int) $report->getId())->getStatusCode());
        $show->setContainer($this->container($user));
        self::assertSame(Response::HTTP_NOT_FOUND, $show(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $show((int) $report->getId())->getStatusCode());
        $show->setContainer($this->container($this->user('other@example.com')));
        self::assertSame(Response::HTTP_FORBIDDEN, $show((int) $report->getId())->getStatusCode());

        $commentList = new ListBugReportCommentsController($reports, $comments, $accessPolicy, $commentFormatter);
        $commentList->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $commentList((int) $report->getId(), Request::create('/'))->getStatusCode());
        $commentList->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $commentList((int) $report->getId(), Request::create('/'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $commentList(999, Request::create('/'))->getStatusCode());
        $commentList->setContainer($this->container($this->user('comment-other@example.com')));
        self::assertSame(Response::HTTP_FORBIDDEN, $commentList((int) $report->getId(), Request::create('/'))->getStatusCode());

        $commentWriter = new BugReportCommentWriter($persistence, $activity, $notifier, $this->users($em));
        $createComment = new CreateBugReportCommentController($reports, $accessPolicy, $commentWriter, $commentFormatter);
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

        $download = new DownloadBugReportAttachmentController($reports, $storage, $accessPolicy);
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

    /**
     * @return array{User, User, BetaCampaign, BugReport}
     */
    private function persistBugGraph(EntityManager $em): array
    {
        $user = $this->user('reporter@example.com');
        $admin = $this->user('admin@example.com', ['ROLE_ADMIN']);
        $campaign = (new BetaCampaign('Campaign', 'Desc', new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('+1 day')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $profile = (new BetaTesterProfile($user, ['weekdays'], 'Motivation', 'manual', 'clear', 'advanced', 'none', ['nvda'], ['windows'], ['chrome'], ['ui'], new \DateTimeImmutable(), '2026-07-26'))->setStatus(BetaTesterProfile::STATUS_ACCEPTED);
        $report = new BugReport($user, $campaign, 'Bug', 'Desc', 'Expected', 'Actual', 'high', '/page', ['screen.png', '']);
        $comment = new BugReportComment($report, $user, 'Hello');
        foreach ([$user, $admin, $campaign, $profile, $report, $comment] as $entity) {
            $em->persist($entity);
        }
        $em->flush();

        return [$user, $admin, $campaign, $report];
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

    /** @param list<string> $roles */
    private function user(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed')->setRoles($roles);

        return $user;
    }

    /** @param array<string, mixed> $override */
    private function profilePayload(array $override = []): array
    {
        return $override + [
            'availability' => ['weekdays'],
            'motivation' => 'Motivation',
            'testingExperience' => ['manual'],
            'bugDescriptionAbility' => ['clear'],
            'technicalKnowledge' => ['advanced'],
            'assistiveTools' => ['nvda'],
            'devices' => ['windows'],
            'browsers' => ['chrome'],
            'testingTypes' => ['ui'],
            'betaConsent' => true,
        ];
    }

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    private function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-beta-tests';
        if (!is_dir($dir.'/var/beta-attachments')) {
            mkdir($dir.'/var/beta-attachments', 0777, true);
        }

        return $dir;
    }

    private function container(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
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

    private function comments(EntityManager $em): BugReportCommentRepository
    {
        return new BugReportCommentRepository($this->registry($em));
    }

    private function users(EntityManager $em): UserRepository
    {
        return new UserRepository($this->registry($em));
    }
}
