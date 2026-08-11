<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\BetaTest;

use App\Module\Admin\Application\BetaTest\Handler\AssignBugReportHandler;
use App\Module\Admin\Application\BetaTest\Handler\ChangeBetaTesterStatusHandler;
use App\Module\Admin\Application\BetaTest\Handler\ChangeBugReportStatusHandler;
use App\Module\Admin\Application\BetaTest\Handler\CloseElapsedBetaCampaignsHandler;
use App\Module\Admin\Application\BetaTest\Handler\CreateBetaCampaignHandler;
use App\Module\Admin\Application\BetaTest\Handler\DeleteBetaCampaignHandler;
use App\Module\Admin\Application\BetaTest\Handler\DeleteBetaTesterHandler;
use App\Module\Admin\Application\BetaTest\Handler\DeleteBugReportHandler;
use App\Module\Admin\Application\BetaTest\Handler\MarkBugReportDuplicateHandler;
use App\Module\Admin\Application\BetaTest\Handler\UpdateBetaCampaignHandler;
use App\Module\Admin\Application\BetaTest\Mapper\BetaCampaignPayloadMapper;
use App\Module\Admin\Application\BetaTest\Provider\BugReportReferenceProvider;
use App\Module\Admin\Application\BetaTest\Provider\BugReportStatusLabelProvider;
use App\Module\BetaTest\Application\Workflow\BugReportActivityLogger;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\BetaTest\Infrastructure\Storage\BetaAttachmentStorage;
use App\Module\BetaTest\Application\Projection\BugReportResponseFormatter;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validation;

abstract class AdminBetaTestIntegrationTestCase extends TestCase
{
    /** @return array{User, User, BetaCampaign, BugReport, BugReport} */
    protected function seed(EntityManager $em): array
    {
        $reporter = $this->user('reporter-admin-beta@example.test');
        $admin = $this->user('admin-beta@example.test', ['ROLE_ADMIN']);
        $campaign = (new BetaCampaign('Campaign', 'Desc', new \DateTimeImmutable('2026-08-10'), new \DateTimeImmutable('2026-08-20')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $closedCampaign = (new BetaCampaign('Closed', 'Desc', new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-08-09')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $profile = (new BetaTesterProfile([
            'user' => $reporter,
            'availability' => ['weekdays'],
            'motivation' => 'Motivation',
            'testingExperience' => 'manual',
            'bugDescriptionAbility' => 'clear',
            'technicalKnowledge' => 'advanced',
            'accessibilityNeed' => 'none',
            'assistiveTools' => ['nvda'],
            'devices' => ['windows'],
            'browsers' => ['chrome'],
            'testingTypes' => ['ui'],
            'consentAt' => new \DateTimeImmutable('2026-08-11T09:00:00+00:00'),
            'privacyNoticeVersion' => '2026-08-04',
        ]))->setStatus(BetaTesterProfile::STATUS_ACCEPTED);
        $report = new BugReport([
            'reporter' => $reporter,
            'campaign' => $campaign,
            'title' => 'Bug',
            'description' => 'Desc',
            'expectedBehavior' => 'Expected',
            'actualBehavior' => 'Actual',
            'severity' => 'high',
            'pageUrl' => '/page',
            'attachments' => ['screen.png'],
        ]);
        $duplicate = new BugReport([
            'reporter' => $reporter,
            'campaign' => $campaign,
            'title' => 'Bug duplicate',
            'description' => 'Desc',
            'expectedBehavior' => null,
            'actualBehavior' => null,
            'severity' => 'normal',
            'pageUrl' => null,
            'attachments' => [],
        ]);
        foreach ([$reporter, $admin, $campaign, $closedCampaign, $profile, $report, $duplicate, new BugReportComment($report, $reporter, 'Hello'), new BugReportActivity($report, $admin, 'created', null, null, 'Created')] as $entity) {
            $em->persist($entity);
        }
        $em->flush();

        return [$reporter, $admin, $campaign, $report, $duplicate];
    }

    /** @param list<string> $roles */
    protected function user(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed')->setRoles($roles);

        return $user;
    }

    protected function notifier(EntityManager $em): UserCommunicationNotifier
    {
        return \App\Tests\Support\UserCommunicationNotifierFactory::create(
            $this,
            new AccountNotificationEventRepository($this->registry($em)),
            new DoctrineUnitOfWork($em),
            $this->createMock(EmailSender::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    protected function entityManager(): EntityManager
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

    protected function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    protected function campaigns(EntityManager $em): BetaCampaignRepository
    {
        return new BetaCampaignRepository($this->registry($em));
    }

    protected function profiles(EntityManager $em): BetaTesterProfileRepository
    {
        return new BetaTesterProfileRepository($this->registry($em));
    }

    protected function reports(EntityManager $em): BugReportRepository
    {
        return new BugReportRepository($this->registry($em));
    }

    protected function users(EntityManager $em): UserRepository
    {
        return new UserRepository($this->registry($em));
    }

    protected function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    protected function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    protected function container(User $user): Container
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    protected function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-admin-beta-tests';
        if (!is_dir($dir.'/var/beta-attachments')) {
            mkdir($dir.'/var/beta-attachments', 0777, true);
        }

        return $dir;
    }

    protected function formatter(): BugReportResponseFormatter
    {
        return new BugReportResponseFormatter();
    }

    protected function activityLogger(DoctrineUnitOfWork $persistence): BugReportActivityLogger
    {
        return new BugReportActivityLogger($persistence);
    }

    protected function campaignPayloads(): BetaCampaignPayloadMapper
    {
        return new BetaCampaignPayloadMapper();
    }

    protected function createCampaignHandler(DoctrineUnitOfWork $persistence): CreateBetaCampaignHandler
    {
        return new CreateBetaCampaignHandler($persistence, $this->campaignPayloads());
    }

    protected function updateCampaignHandler(DoctrineUnitOfWork $persistence): UpdateBetaCampaignHandler
    {
        return new UpdateBetaCampaignHandler($persistence, $this->campaignPayloads());
    }

    protected function deleteCampaignHandler(DoctrineUnitOfWork $persistence): DeleteBetaCampaignHandler
    {
        return new DeleteBetaCampaignHandler($persistence);
    }

    protected function closeElapsedCampaignsHandler(DoctrineUnitOfWork $persistence): CloseElapsedBetaCampaignsHandler
    {
        return new CloseElapsedBetaCampaignsHandler($persistence);
    }

    protected function changeTesterStatusHandler(DoctrineUnitOfWork $persistence): ChangeBetaTesterStatusHandler
    {
        return new ChangeBetaTesterStatusHandler($persistence);
    }

    protected function deleteTesterHandler(DoctrineUnitOfWork $persistence): DeleteBetaTesterHandler
    {
        return new DeleteBetaTesterHandler($persistence);
    }

    protected function assignBugReportHandler(DoctrineUnitOfWork $persistence, BugReportActivityLogger $activity): AssignBugReportHandler
    {
        return new AssignBugReportHandler($persistence, $activity);
    }

    protected function changeBugReportStatusHandler(DoctrineUnitOfWork $persistence, BugReportActivityLogger $activity, UserCommunicationNotifier $notifier): ChangeBugReportStatusHandler
    {
        return new ChangeBugReportStatusHandler($persistence, $activity, $notifier, new BugReportStatusLabelProvider());
    }

    protected function bugReportReferenceProvider(EntityManager $em): BugReportReferenceProvider
    {
        return new BugReportReferenceProvider($this->reports($em));
    }

    protected function markBugReportDuplicateHandler(DoctrineUnitOfWork $persistence, BugReportActivityLogger $activity, UserCommunicationNotifier $notifier): MarkBugReportDuplicateHandler
    {
        return new MarkBugReportDuplicateHandler($persistence, $activity, $notifier);
    }

    protected function deleteBugReportHandler(DoctrineUnitOfWork $persistence): DeleteBugReportHandler
    {
        return new DeleteBugReportHandler($persistence, new BetaAttachmentStorage($this->projectDir(), $this->createMock(LoggerInterface::class)));
    }
}
