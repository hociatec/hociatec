<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Service;

use App\Module\Marketing\Entity\EmailTemplate;
use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Notification\Entity\AccountNotificationEvent;
use App\Module\Notification\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Service\CommunicationPreferences;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Module\User\Entity\User;
use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Service\VoucherNotificationEmailService;
use App\Shared\Persistence\DoctrinePersistence;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

final class VoucherNotificationEmailServiceTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testSendCustomerVoucherStoresInternalNotificationAndSkipsEmailWhenPreferenceIsMissing(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->expects(self::never())->method('findActiveOneByScenarioKey');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION]);
        $voucher = $this->voucher('WELCOME10');

        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test/',
            'noreply@example.com',
        );

        $service->sendCustomerVoucher($user, $voucher);

        self::assertTrue($this->notificationRepository($this->entityManager())->existsForKey('voucher:55:customer_offer'));
        self::assertSame(1, $this->notificationRepository($this->entityManager())->countForUser($user));
    }

    public function testSendCustomerVoucherRendersFallbackTemplateWithHtmlAndText(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->expects(self::once())
            ->method('findActiveOneByScenarioKey')
            ->with('customer_voucher_offer')
            ->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                return 'Votre bon de réduction CODE10' === $email->getSubject()
                    && str_contains($email->getHtmlBody() ?? '', '10,00 EUR')
                    && str_contains($email->getHtmlBody() ?? '', 'https://front.example.test/panier')
                    && str_contains($email->getTextBody() ?? '', 'Utilisez-le sur votre prochaine commande');
            }));

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL], '<Ada>');
        $voucher = $this->voucher('CODE10');
        $voucher->setDescription('Remise de bienvenue');

        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test/',
            'noreply@example.com',
        );

        $service->sendCustomerVoucher($user, $voucher);

        self::assertTrue($this->notificationRepository($this->entityManager())->existsForKey('voucher:55:customer_offer'));
    }

    public function testSendCustomerVoucherUsesCustomTemplateAndEscapesInjectedHtmlValues(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->expects(self::once())
            ->method('findActiveOneByScenarioKey')
            ->with('customer_voucher_offer')
            ->willReturn(new EmailTemplate(
                'Voucher',
                'voucher',
                'customer_voucher_offer',
                'Sujet {{voucher_code}}',
                '<p>{{first_name}} {{voucher_description}}</p>',
                'Texte {{voucher_code}} {{voucher_description}}'
            ));

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return 'Sujet VIP20' === $email->getSubject()
                    && str_contains($email->getHtmlBody() ?? '', '&lt;b&gt;unsafe&lt;/b&gt;')
                    && str_contains($email->getTextBody() ?? '', '<b>unsafe</b>');
            }));

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $voucher = $this->voucher('VIP20');
        $voucher->setDescription('<b>unsafe</b>');

        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $service->sendCustomerVoucher($user, $voucher);

        self::assertTrue($this->notificationRepository($this->entityManager())->existsForKey('voucher:55:customer_offer'));
        self::assertSame(1, $this->notificationRepository($this->entityManager())->countForUser($user));
    }

    public function testSendCustomerVoucherRendersPercentVoucherValueLabel(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->method('findActiveOneByScenarioKey')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return str_contains($email->getHtmlBody() ?? '', '25%')
                    && str_contains($email->getTextBody() ?? '', '25%');
            }));

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $voucher = $this->voucher('PERCENT25');
        $voucher->setDiscountType(Voucher::TYPE_PERCENT)->setDiscountValue(25);

        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $service->sendCustomerVoucher($user, $voucher);
    }

    public function testSendCustomerVoucherLogsAndRethrowsMailerFailures(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->method('findActiveOneByScenarioKey')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $logger,
            'https://front.example.test',
            'noreply@example.com',
        );

        try {
            $service->sendCustomerVoucher(
                $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]),
                $this->voucher('FAIL10'),
            );
            self::fail('Expected mailer exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('smtp down', $exception->getMessage());
        }
    }

    public function testSendCustomerVoucherRejectsIneligibleVoucherAndUnknownTemplateVariables(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->method('findActiveOneByScenarioKey')->willReturn(
            new EmailTemplate(
                'Voucher',
                'voucher',
                'customer_voucher_offer',
                'Sujet {{voucher_code}}',
                '<p>{{unknown_placeholder}}</p>',
                'Texte {{voucher_code}}'
            )
        );
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $logger,
            'https://front.example.test',
            'noreply@example.com',
        );

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $inactiveVoucher = $this->voucher('OFF10')->setIsActive(false);

        try {
            $service->sendCustomerVoucher($user, $inactiveVoucher);
            self::fail('Expected inactive voucher exception.');
        } catch (\DomainException $exception) {
            self::assertSame('Impossible de notifier un voucher inactif.', $exception->getMessage());
        }

        try {
            $service->sendCustomerVoucher($user, $this->voucher('TPL10'));
            self::fail('Expected unresolved placeholder exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Le template contient une variable inconnue.', $exception->getMessage());
        }
    }

    public function testSendCustomerVoucherKeepsUserIdVoucherValidAfterEmailChange(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->method('findActiveOneByScenarioKey')->willReturn(null);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $voucher = $this->voucher('PRIVATE')
            ->setRecipientUserId($user->getId())
            ->setRecipientEmail('previous@example.com');

        $service->sendCustomerVoucher($user, $voucher);

        self::assertTrue($this->notificationRepository($this->entityManager())->existsForKey('voucher:55:customer_offer'));
    }

    public function testSendCustomerVoucherRejectsVoucherWhenUserIdConstraintDoesNotMatch(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $voucher = $this->voucher('PRIVATE')
            ->setRecipientUserId(99)
            ->setRecipientEmail('ada@example.com');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Impossible de notifier un voucher attribué à un autre destinataire.');

        $service->sendCustomerVoucher($user, $voucher);
    }

    public function testSendCustomerVoucherRejectsFutureAndExpiredVouchers(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        $service = new VoucherNotificationEmailService(
            $templates,
            $mailer,
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);

        try {
            $service->sendCustomerVoucher($user, $this->voucher('FUTURE')->setStartsAt(new \DateTimeImmutable('+1 day')));
            self::fail('Expected future voucher exception.');
        } catch (\DomainException $exception) {
            self::assertSame('Impossible de notifier un voucher qui n\'est pas encore disponible.', $exception->getMessage());
        }

        try {
            $service->sendCustomerVoucher($user, $this->voucher('EXPIRED')->setEndsAt(new \DateTimeImmutable('-1 day')));
            self::fail('Expected expired voucher exception.');
        } catch (\DomainException $exception) {
            self::assertSame('Impossible de notifier un voucher expiré.', $exception->getMessage());
        }
    }

    private function voucher(string $code): Voucher
    {
        $voucher = new Voucher('Voucher', $code, Voucher::TYPE_FIXED_CENTS, 1000);
        $this->setId($voucher, 55);

        return $voucher;
    }

    /**
     * @param list<string> $preferences
     */
    private function persistUser(array $preferences, string $firstName = 'Ada'): User
    {
        $user = new User('ada@example.com', $firstName, 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setCommunicationPreferences($preferences);
        $this->setId($user, 42);
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function notifier(): UserCommunicationNotifier
    {
        return new UserCommunicationNotifier(
            $this->notificationRepository($this->entityManager()),
            new DoctrinePersistence($this->entityManager()),
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

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
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

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
