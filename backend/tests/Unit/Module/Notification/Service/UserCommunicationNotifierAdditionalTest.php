<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification\Service;

use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

final class UserCommunicationNotifierAdditionalTest extends TestCase
{
    public function testNotifyInternalSkipsWhenNotificationPreferenceIsMissing(): void
    {
        $repository = $this->notificationRepository($this->notificationEntityManager());
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $notifier = new UserCommunicationNotifier(
            $repository,
            new DoctrineUnitOfWork($entityManager),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.com',
        );

        $user = $this->user();
        $user->setCommunicationPreferences([CommunicationPreferences::EMAIL]);

        $notifier->notifyInternal($user, 'key-1', 'Titre', 'Message', '/orders/1', 'order');

        self::assertFalse($repository->existsForKey('key-1'));
    }

    public function testNotifyInternalLogsFailureWhenPersistenceThrows(): void
    {
        $repository = $this->notificationRepository($this->notificationEntityManager());
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->willThrowException(new \RuntimeException('db down'));
        $entityManager->expects(self::never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Internal account notification failed.',
                self::callback(static function (array $context): bool {
                    return 77 === $context['userId']
                        && 'key-2' === $context['key']
                        && 'beta_comment' === $context['type']
                        && $context['exception'] instanceof \RuntimeException;
                }),
            );

        $notifier = new UserCommunicationNotifier(
            $repository,
            new DoctrineUnitOfWork($entityManager),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $logger,
            'noreply@example.com',
            'https://front.example.com',
        );

        $user = $this->user();
        $user->setCommunicationPreferences([CommunicationPreferences::NOTIFICATION]);
        $this->setId($user, 77);

        $notifier->notifyInternal($user, 'key-2', 'Titre', 'Message', '/beta/feedback', 'beta_comment');
    }

    public function testNotifyInternalSkipsDuplicateNotificationKey(): void
    {
        $entityManager = $this->notificationEntityManager();
        $repository = $this->notificationRepository($entityManager);
        $user = $this->user();
        $user->setPassword('hashed');
        $user->setCommunicationPreferences([CommunicationPreferences::NOTIFICATION]);
        $this->setId($user, 77);
        $entityManager->persist($user);
        $entityManager->persist(new AccountNotificationEvent($user, 'key-duplicate', 'Titre', 'Message', '/orders/1', 'order'));
        $entityManager->flush();

        $persistenceEntityManager = $this->createMock(EntityManagerInterface::class);
        $persistenceEntityManager->expects(self::never())->method('persist');
        $persistenceEntityManager->expects(self::never())->method('flush');

        $notifier = new UserCommunicationNotifier(
            $repository,
            new DoctrineUnitOfWork($persistenceEntityManager),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.com',
        );

        $notifier->notifyInternal($user, 'key-duplicate', 'Titre', 'Message', '/orders/1', 'order');

        self::assertTrue($repository->existsForKey('key-duplicate'));
        self::assertSame(1, $repository->countForUser($user));
    }

    public function testNotifySkipsDispatchWhenUserHasNoId(): void
    {
        $repository = $this->notificationRepository($this->notificationEntityManager());
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $notifier = new UserCommunicationNotifier(
            $repository,
            new DoctrineUnitOfWork($entityManager),
            $this->createMock(MailerInterface::class),
            $bus,
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.com/',
        );

        $user = $this->user();
        $user->setCommunicationPreferences([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);

        $notifier->notify($user, 'key-3', 'Titre', 'Message', '/actualites/1', 'news_article');
        self::assertNull($user->getId());
    }

    public function testSendEmailNowRendersBetaNewsAndDefaultLabels(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(3))
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                $text = $email->getTextBody() ?? '';

                return str_contains($text, 'Accéder à l’espace bêta')
                    || str_contains($text, 'Lire l’actualité')
                    || str_contains($text, 'Consulter le suivi');
            }));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $notifier = new UserCommunicationNotifier(
            $this->notificationRepository($this->notificationEntityManager()),
            new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)),
            $mailer,
            $this->createMock(MessageBusInterface::class),
            $logger,
            'noreply@example.com',
            'https://front.example.com/',
        );

        $user = $this->user();

        $notifier->sendEmailNow($user, 'Titre', 'Message', '/beta/dashboard', 'beta_news');
        $notifier->sendEmailNow($user, 'Titre', 'Message', '/actualites/1', 'news_article');
        $notifier->sendEmailNow($user, 'Titre', 'Message', '/orders/1', 'order');
    }

    public function testDispatchEmailLogsBusFailure(): void
    {
        $entityManager = $this->notificationEntityManager();
        $repository = $this->notificationRepository($entityManager);
        $persistence = new DoctrineUnitOfWork($entityManager);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willThrowException(new \RuntimeException('bus down'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Communication email dispatch failed.',
                self::callback(static fn (array $context): bool => 1 === $context['userId']
                    && 'order' === $context['type']
                    && $context['exception'] instanceof \RuntimeException),
            );

        $notifier = new UserCommunicationNotifier(
            $repository,
            $persistence,
            $this->createMock(MailerInterface::class),
            $bus,
            $logger,
            'noreply@example.com',
            'https://front.example.com',
        );

        $user = $this->user();
        $user->setPassword('hashed');
        $user->setCommunicationPreferences([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $this->setId($user, 88);
        $entityManager->persist($user);
        $entityManager->flush();

        $notifier->notify($user, 'key-4', 'Titre', 'Message', '/orders/1', 'order');
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

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
