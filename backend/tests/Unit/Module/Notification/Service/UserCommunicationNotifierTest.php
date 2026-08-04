<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification\Service;

use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Application\Message\UserCommunicationEmailMessage;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Service\CommunicationPreferences;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

final class UserCommunicationNotifierTest extends TestCase
{
    public function testNotifierStoresInternalNotificationsAndQueuesEmails(): void
    {
        $entityManager = $this->notificationEntityManager();
        $persistence = new DoctrineUnitOfWork($entityManager);
        $mailer = $this->createMock(MailerInterface::class);
        $messages = [];
        $bus = new class($messages) implements MessageBusInterface {
            public array $messages = [];
            public function __construct(array &$messages) { $this->messages = &$messages; }
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->messages[] = $message;
                return new Envelope($message, $stamps);
            }
        };
        $logger = $this->createMock(LoggerInterface::class);
        $notifier = new UserCommunicationNotifier(
            $this->notificationRepository($entityManager),
            $persistence,
            $mailer,
            $bus,
            $logger,
            'noreply@example.com',
            'https://front.example.com',
        );

        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setCommunicationPreferences([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL, CommunicationPreferences::NEWS_EMAIL]);
        $this->setId($user, 9);
        $entityManager->persist($user);
        $entityManager->flush();

        $notifier->notify($user, 'key-1', 'Title', 'Body', '/orders/1', 'order');
        self::assertCount(1, $messages);
        self::assertInstanceOf(UserCommunicationEmailMessage::class, $messages[0]);
        self::assertTrue($notifier->shouldSendEmail($user));
        self::assertTrue($notifier->shouldSendNewsEmail($user));
        self::assertTrue($this->notificationRepository($entityManager)->existsForKey('key-1'));

        $notifier->notify($user, 'key-1', 'Title', 'Body', '/orders/1', 'order');
        self::assertCount(1, $messages);
        self::assertSame(1, $this->notificationRepository($entityManager)->countForUser($user));

        $user->setCommunicationPreferences([CommunicationPreferences::EMAIL]);
        $notifier->notifyInternal($user, 'key-2', 'Title', 'Body', '/orders/2', 'order');
        self::assertFalse($this->notificationRepository($entityManager)->existsForKey('key-2'));
        self::assertFalse($notifier->shouldSendNewsEmail($user));
    }

    public function testSendEmailNowBuildsExpectedEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                return 'Title' === $email->getSubject()
                    && 'ada@example.com' === $email->getTo()[0]->getAddress()
                    && str_contains($email->getTextBody() ?? '', 'https://front.example.com/orders/1');
            }));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $notifier = new UserCommunicationNotifier(
            $this->notificationRepository($this->notificationEntityManager()),
            new DoctrineUnitOfWork($this->notificationEntityManager()),
            $mailer,
            $this->createMock(MessageBusInterface::class),
            $logger,
            'noreply@example.com',
            'https://front.example.com',
        );
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        $notifier->sendEmailNow($user, 'Title', 'Body', '/orders/1', 'order');
    }

    public function testSendEmailNowLogsFailureWhenMailerThrows(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $notifier = new UserCommunicationNotifier(
            $this->notificationRepository($this->notificationEntityManager()),
            new DoctrineUnitOfWork($this->notificationEntityManager()),
            $mailer,
            $this->createMock(MessageBusInterface::class),
            $logger,
            'noreply@example.com',
            'https://front.example.com',
        );
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        $notifier->sendEmailNow($user, 'Title', 'Body', '/orders/1', 'order');
    }

    private function notificationEntityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(AccountNotificationEvent::class),
        ]);

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
