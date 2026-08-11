<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\BetaTest;

use App\Module\BetaTest\Application\Workflow\BugReportActivityLogger;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportCommentRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\Notification\Application\Notification\CommunicationPreferencePolicy;
use App\Module\Notification\Application\Notification\InternalAccountNotificationSender;
use App\Module\Notification\Application\Notification\UserCommunicationEmailSender;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
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
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class BetaTestIntegrationTestCase extends TestCase
{
    /**
     * @return array{User, User, BetaCampaign, BugReport}
     */
    protected function persistBugGraph(EntityManager $em): array
    {
        $user = $this->user('reporter@example.com');
        $admin = $this->user('admin@example.com', ['ROLE_ADMIN']);
        $campaign = (new BetaCampaign('Campaign', 'Desc', new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('+1 day')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $profile = (new BetaTesterProfile([
            'user' => $user,
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
            'consentAt' => new \DateTimeImmutable(),
            'privacyNoticeVersion' => '2026-07-26',
        ]))->setStatus(BetaTesterProfile::STATUS_ACCEPTED);
        $report = new BugReport([
            'reporter' => $user,
            'campaign' => $campaign,
            'title' => 'Bug',
            'description' => 'Desc',
            'expectedBehavior' => 'Expected',
            'actualBehavior' => 'Actual',
            'severity' => 'high',
            'pageUrl' => '/page',
            'attachments' => ['screen.png', ''],
        ]);
        $comment = new BugReportComment($report, $user, 'Hello');
        foreach ([$user, $admin, $campaign, $profile, $report, $comment] as $entity) {
            $em->persist($entity);
        }
        $em->flush();

        return [$user, $admin, $campaign, $report];
    }

    protected function notifier(EntityManager $em): UserCommunicationNotifier
    {
        $repository = new AccountNotificationEventRepository($this->registry($em));
        $persistence = new DoctrineUnitOfWork($em);
        $logger = $this->createMock(LoggerInterface::class);
        $preferences = new CommunicationPreferencePolicy();

        return \App\Tests\Support\UserCommunicationNotifierFactory::create(
            $this,
            $repository,
            $preferences,
            new InternalAccountNotificationSender($repository, $persistence, $preferences, $logger),
            new UserCommunicationEmailSender(
                $this->createMock(EmailSender::class),
                $this->createMock(AsyncMessageDispatcher::class),
                $logger,
                'noreply@example.com',
                'https://front.example.test',
            ),
        );
    }

    /** @param list<string> $roles */
    protected function user(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed')->setRoles($roles);

        return $user;
    }

    /** @param array<string, mixed> $override */
    protected function profilePayload(array $override = []): array
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

    protected function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    protected function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-beta-tests';
        if (!is_dir($dir.'/var/beta-attachments')) {
            mkdir($dir.'/var/beta-attachments', 0777, true);
        }

        return $dir;
    }

    protected function container(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
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

    protected function comments(EntityManager $em): BugReportCommentRepository
    {
        return new BugReportCommentRepository($this->registry($em));
    }

    protected function users(EntityManager $em): UserRepository
    {
        return new UserRepository($this->registry($em));
    }
}
