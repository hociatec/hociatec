<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Contact\Application\Service\ContactAcknowledgementSender;
use App\Module\Contact\Application\Service\ContactNotificationSender;
use App\Module\Marketing\Application\Service\EmailTemplateRenderer;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Marketing\Application\Service\MarketingAudienceProvider;
use App\Module\Marketing\Application\Service\MarketingCampaignSender;
use App\Module\Marketing\Application\Service\MarketingRecipientContextProvider;
use App\Module\Marketing\Application\Service\EmailTemplateScenarioProvider;
use App\Module\Marketing\Application\Service\MarketingTemplateRenderer;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Service\CommunicationPreferences;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\Auth\Application\Outbox\SendPasswordResetEmailHandler;
use App\Module\Auth\Application\Service\PasswordResetEmailService;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

        $mailer = $this->createMock(MailerInterface::class);
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

        $failingMailer = $this->createMock(MailerInterface::class);
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
        $user->setPasswordResetToken('current-token')->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->onlyMethods(['findOneByEmailInsensitive'])->getMock();
        $repository->expects(self::exactly(2))->method('findOneByEmailInsensitive')->willReturn($user);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::isInstanceOf(Email::class));
        $emails = new PasswordResetEmailService(
            $mailer,
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $handler = new SendPasswordResetEmailHandler($repository, $emails);
        self::assertTrue($handler->supports(new OutboxEvent('reset-1', 'auth.password_reset_email_requested', ['email' => $user->getEmail()])));
        $handler->handle(new OutboxEvent('auth.password_reset.'.hash('sha256', 'current-token'), 'auth.password_reset_email_requested', ['email' => $user->getEmail()]));
        $handler->handle(new OutboxEvent('auth.password_reset.'.hash('sha256', 'stale-token'), 'auth.password_reset_email_requested', ['email' => $user->getEmail()]));
    }

    public function testContactSendersUseMailerWithExpectedContent(): void
    {
        $renderer = new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class));
        $input = new ContactInput('Ada', 'ada@example.com', 'Sujet', 'Bonjour');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(2))
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return str_contains($email->getSubject(), 'Sujet')
                    || str_contains($email->getSubject(), 'Merci de nous avoir contactés');
            }));

        (new ContactAcknowledgementSender($renderer, $mailer, 'noreply@example.com'))->send($input);
        (new ContactNotificationSender($renderer, $mailer, 'noreply@example.com', 'contact@example.com'))->send($input);
    }

    public function testMarketingCampaignSenderUsesMailerAndPersistsCampaign(): void
    {
        $optIn = $this->user('news@example.com');
        $optIn->setIsVerified(true);
        $optIn->setCommunicationPreferences([CommunicationPreferences::NEWS_EMAIL]);
        $noOptIn = $this->user('off@example.com');
        $noOptIn->setIsVerified(true);
        $noOptIn->setCommunicationPreferences([CommunicationPreferences::EMAIL]);

        $audienceQuery = $this->queryMock(['getResult' => [$optIn, $noOptIn]]);
        $statsQuery = $this->queryMock(['getSingleResult' => [
            'ordersCount' => 0,
            'lastOrderAt' => null,
            'totalSpentCents' => 0,
        ]]);
        $lastOrderQuery = $this->queryMock(['getOneOrNullResult' => null]);
        $pendingReviewsQuery = $this->queryMock(['getSingleScalarResult' => '0']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(4))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $this->queryBuilderMock($audienceQuery),
                $this->queryBuilderMock($statsQuery),
                $this->queryBuilderMock($lastOrderQuery),
                $this->queryBuilderMock($pendingReviewsQuery),
            );
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (object $campaign): bool {
                return $campaign instanceof \App\Module\Marketing\Domain\Entity\EmailCampaign
                    && 'Campagne' === $campaign->getName()
                    && 2 === $campaign->getRecipientsCount();
            }));
        $entityManager->expects(self::once())->method('flush');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return 'Sujet Ada' === $email->getSubject()
                    && str_contains($email->getHtmlBody() ?? '', 'Ada')
                    && str_contains($email->getTextBody() ?? '', 'Ada');
            }));

        $sender = new MarketingCampaignSender(
            new MarketingAudienceProvider(new \App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingAudienceQuery($entityManager), new EmailTemplateScenarioProvider()),
            new MarketingRecipientContextProvider(new \App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingRecipientContextQuery($entityManager), 'https://front.example.test'),
            new MarketingTemplateRenderer(),
            $mailer,
            $this->notifier(),
            new DoctrineUnitOfWork($entityManager),
            'noreply@example.com',
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
        self::assertSame(2, $campaign->getRecipientsCount());
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
        return new UserCommunicationNotifier(
            $this->notificationRepository($this->entityManager()),
            new DoctrineUnitOfWork($this->entityManager()),
            $this->createMock(MailerInterface::class),
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
