<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Auth\Application\Outbox\SendPasswordResetEmailHandler;
use App\Module\Auth\Application\Workflow\PasswordResetEmailService;
use App\Module\Auth\Application\Workflow\PasswordResetTokenHasher;
use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Contact\Application\Notification\ContactAcknowledgementSender;
use App\Module\Contact\Application\Notification\ContactNotificationSender;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Marketing\Application\Notification\MarketingCampaignSender;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Outbox\Application\Outbox;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

final class RemainingMailSendersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testPasswordResetEmailServiceUsesMailerAndLogsFailures(): void
    {
        $user = $this->user('ada@example.com');

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return str_contains($email->getSubject(), 'Réinitialisez votre mot de passe')
                    && str_contains($email->getTextBody() ?? '', '/reset-password/')
                    && str_contains($email->getHtmlBody() ?? '', '/reset-password/');
            }));

        $service = new PasswordResetEmailService(
            $mailer,
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );
        $service->send($user, 'reset-token');

        $failingMailer = $this->createMock(EmailSender::class);
        $failingMailer->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $service2 = new PasswordResetEmailService(
            $failingMailer,
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $logger,
            'https://front.example.test',
            'noreply@example.com',
        );
        $this->expectException(\RuntimeException::class);
        $service2->send($user, 'reset-token');
    }

    public function testPasswordResetOutboxHandlerSendsOnlyCurrentValidToken(): void
    {
        $user = $this->user('reset-handler@example.com');
        $user->setPasswordResetToken(PasswordResetTokenHasher::hash('current-token'))->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->onlyMethods(['findOneByEmailInsensitive'])->getMock();
        $repository->expects(self::exactly(2))->method('findOneByEmailInsensitive')->willReturn($user);

        $eventKey = 'auth.password_reset.'.hash('sha256', 'current-token');
        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::once())->method('send')->with(self::callback(static function (Email $email) use ($eventKey): bool {
            return $eventKey === $email->getHeaders()->get('X-Hociatec-Idempotency-Key')?->getBodyAsString();
        }));
        $emails = new PasswordResetEmailService(
            $mailer,
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $handler = new SendPasswordResetEmailHandler($repository, $emails);
        self::assertTrue($handler->supports(new OutboxEvent('reset-1', 'auth.password_reset_email_requested', ['email' => $user->getEmail(), 'token' => 'current-token'])));
        $handler->handle(new OutboxEvent($eventKey, 'auth.password_reset_email_requested', ['email' => $user->getEmail(), 'token' => 'current-token']));
        $handler->handle(new OutboxEvent('auth.password_reset.'.hash('sha256', 'stale-token'), 'auth.password_reset_email_requested', ['email' => $user->getEmail(), 'token' => 'stale-token']));
    }

    public function testContactSendersUseMailerWithExpectedContent(): void
    {
        $renderer = new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class));
        $input = new ContactInput('Ada', 'ada@example.com', 'Sujet', 'Bonjour');

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::exactly(2))
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return str_contains($email->getSubject(), 'Sujet')
                    || str_contains($email->getSubject(), 'Merci de nous avoir contactés');
            }));

        (new ContactAcknowledgementSender($renderer, $mailer, 'noreply@example.com'))->send($input);
        (new ContactNotificationSender($renderer, $mailer, 'noreply@example.com', 'contact@example.com'))->send($input);
    }

    public function testMarketingCampaignSenderQueuesEligibleRecipientsAndPersistsCampaign(): void
    {
        $entityManager = $this->entityManager();
        $persistence = new DoctrineUnitOfWork($entityManager);

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::never())->method('send');

        $sender = new MarketingCampaignSender(
            $persistence,
            new DoctrineTransactionManager($entityManager),
            new Outbox($persistence),
        );

        $campaign = $sender->send(
            'Campagne',
            'all_verified_users',
            ['key' => 'value'],
            'Sujet {{first_name}}',
            '<p>{{first_name}}</p>',
            'Texte {{first_name}}',
            null,
            'admin@example.com',
        );

        self::assertSame('Campagne', $campaign->getName());
        self::assertSame(0, $campaign->getRecipientsCount());
        self::assertSame(0, $campaign->getPendingCount());
        self::assertSame(0, $campaign->getSkippedCount());
        self::assertSame(1, $entityManager->getRepository(OutboxEvent::class)->count(['type' => 'marketing.campaign.prepare_requested']));
        self::assertSame(0, $entityManager->getRepository(EmailCampaignRecipient::class)->count([]));
    }

    /** @param array<string, mixed> $results */
    private function queryMock(array $results): Query
    {
        /** @var Query&\PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_keys($results))
            ->getMock();

        foreach ($results as $method => $value) {
            $query->method($method)->willReturn($value);
        }

        return $query;
    }

    private function queryBuilderMock(Query $query): QueryBuilder
    {
        $builder = $this->createMock(QueryBuilder::class);
        foreach (['select', 'from', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults', 'join', 'leftJoin'] as $method) {
            $builder->method($method)->willReturnSelf();
        }
        $builder->method('getQuery')->willReturn($query);

        return $builder;
    }

    private function notifier(): UserCommunicationNotifier
    {
        return \App\Tests\Support\UserCommunicationNotifierFactory::create($this, 
            $this->notificationRepository($this->entityManager()),
            new DoctrineUnitOfWork($this->entityManager()),
            $this->createMock(EmailSender::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(AccountNotificationEvent::class),
            $entityManager->getClassMetadata(EmailTemplate::class),
            $entityManager->getClassMetadata(EmailCampaign::class),
            $entityManager->getClassMetadata(EmailCampaignRecipient::class),
            $entityManager->getClassMetadata(OutboxEvent::class),
        ]);
        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function notificationRepository(EntityManager $entityManager): AccountNotificationEventRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new AccountNotificationEventRepository($registry);
    }

    private function user(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }
}
