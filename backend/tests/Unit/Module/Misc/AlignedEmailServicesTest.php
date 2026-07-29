<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Service\ProductShareEmailService;
use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Module\Notification\Entity\AccountNotificationEvent;
use App\Module\Notification\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Service\CommunicationPreferences;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Module\Order\Entity\Order;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderEventPersistence;
use App\Module\Order\Service\OrderNotificationContentProvider;
use App\Module\Order\Service\OrderNotificationEmailService;
use App\Module\Order\Service\OrderPersistence;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\TradeIn\Entity\TradeInRequest;
use App\Module\TradeIn\Service\TradeInNotificationEmailService;
use App\Module\User\Entity\User;
use App\Shared\Mail\MailDeliveryException;
use App\Shared\Persistence\DoctrinePersistence;
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

final class AlignedEmailServicesTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testProductShareEmailServiceUsesMailerWithHtmlAndTextAndWrapsFailures(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $renderer = new EmailTemplateRenderer($templates);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return 'Découvrir : Phone' === $email->getSubject()
                    && str_contains($email->getHtmlBody() ?? '', 'Voir la fiche produit')
                    && str_contains($email->getTextBody() ?? '', 'Voir la fiche produit');
            }));

        $service = new ProductShareEmailService($renderer, $mailer, 'https://front.example.test', 'noreply@example.com');
        $service->send($this->product(), 'client@example.com');

        $failingMailer = $this->createMock(MailerInterface::class);
        $failingMailer->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $service2 = new ProductShareEmailService($renderer, $failingMailer, 'https://front.example.test', 'noreply@example.com');

        try {
            $service2->send($this->product(), 'client@example.com');
            self::fail('Expected mail delivery exception.');
        } catch (MailDeliveryException $exception) {
            self::assertSame('Email delivery failed for product_share.', $exception->getMessage());
        }
    }

    public function testOrderNotificationEmailServiceUsesMailerAndPersistsSentState(): void
    {
        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $order = new Order('ORD-1', $user);
        $this->setId($order, 12);
        $order->setTotalPriceCents(12345);

        $templates = $this->createMock(EmailTemplateRepository::class);
        $quotes = $this->getMockBuilder(QuoteRepository::class)->disableOriginalConstructor()->getMock();
        $content = new OrderNotificationContentProvider($templates, $quotes, 'https://front.example.test');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return str_contains($email->getSubject(), 'ORD-1')
                    && str_contains($email->getHtmlBody() ?? '', 'https://front.example.test/orders/12')
                    && str_contains($email->getTextBody() ?? '', 'https://front.example.test/orders/12');
            }));

        $orderEm = $this->createMock(EntityManagerInterface::class);
        $orderEm->expects(self::once())->method('flush');
        $eventEm = $this->createMock(EntityManagerInterface::class);
        $eventEm->expects(self::once())->method('persist');
        $eventEm->expects(self::once())->method('flush');

        $service = new OrderNotificationEmailService(
            new OrderPersistence($orderEm),
            $content,
            $mailer,
            new OrderEventLogger(new OrderEventPersistence($eventEm)),
            $this->notifier(),
            'noreply@example.com',
        );

        self::assertTrue($service->sendOrderCreatedIfNeeded($order));
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getOrderCreatedEmailSentAt());
    }

    public function testTradeInNotificationEmailServiceUsesMailerAndLogsFailures(): void
    {
        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $request = new TradeInRequest(
            'TR-1',
            $user,
            'Ada',
            'Lovelace',
            'ada@example.com',
            '0102030405',
            'Phones',
            'iPhone',
            100000,
            2024,
            'Apple',
            '15 Pro',
            null,
            'A',
            true,
            true,
            true,
            'Bon état',
            null,
            null,
            50000,
            60000,
            new \DateTimeImmutable('2026-07-29T10:00:00+00:00'),
        );

        $templates = $this->createMock(EmailTemplateRepository::class);
        $renderer = new EmailTemplateRenderer($templates);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return str_contains($email->getSubject(), 'TR-1')
                    && str_contains($email->getHtmlBody() ?? '', 'reprises')
                    && str_contains($email->getTextBody() ?? '', 'reprises');
            }));

        $service = new TradeInNotificationEmailService(
            $renderer,
            $mailer,
            $this->createMock(LoggerInterface::class),
            $this->notifier(),
            'noreply@example.com',
            'https://front.example.test',
        );
        $service->sendCreated($request);

        $failingMailer = $this->createMock(MailerInterface::class);
        $failingMailer->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $service2 = new TradeInNotificationEmailService(
            $renderer,
            $failingMailer,
            $logger,
            $this->notifier(),
            'noreply@example.com',
            'https://front.example.test',
        );
        $service2->sendCreated($request);
    }

    private function product(): Product
    {
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'SKU-1', 'Desc', 10000, 4, $category);
        $product->setShortDescription('Produit phare');

        return $product;
    }

    /**
     * @param list<string> $preferences
     */
    private function persistUser(array $preferences): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
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

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
