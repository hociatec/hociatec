<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Application\Persistence\OrderEventPersistence;
use App\Module\Order\Application\Provider\OrderNotificationContentProvider;
use App\Module\Order\Application\Workflow\OrderNotificationEmailService;
use App\Module\Order\Application\Persistence\OrderPersistence;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
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

final class OrderNotificationEmailServiceAdditionalTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testOrderCreatedSkipsWhenAlreadySentOrEmailPreferenceMissing(): void
    {
        $order = $this->order([CommunicationPreferences::NOTIFICATION]);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $service = $this->service($mailer, $this->createMock(EntityManagerInterface::class), $this->createMock(EntityManagerInterface::class));

        self::assertFalse($service->sendOrderCreatedIfNeeded($order));
        self::assertNull($order->getOrderCreatedEmailSentAt());
        self::assertSame(1, $this->notificationRepository()->countForUser($order->getUser()));

        $order->setOrderCreatedEmailSentAt(new \DateTimeImmutable());
        self::assertFalse($service->sendOrderCreatedIfNeeded($order));
    }

    public function testInvoiceRequiresDocumentsAndResendPersistsSentStateAndEvent(): void
    {
        $order = $this->order([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::callback(static function (Email $email): bool {
            $html = $email->getHtmlBody();

            return str_contains(strtolower((string) $email->getSubject()), 'facture')
                && is_string($html)
                && str_contains($html, '/orders/51');
        }));

        $orderEntityManager = $this->createMock(EntityManagerInterface::class);
        $orderEntityManager->expects(self::once())->method('flush');
        $eventEntityManager = $this->createMock(EntityManagerInterface::class);
        $eventEntityManager->expects(self::once())->method('persist');
        $eventEntityManager->expects(self::once())->method('flush');

        $service = $this->service($mailer, $orderEntityManager, $eventEntityManager);

        self::assertFalse($service->sendInvoiceIssuedIfNeeded($order));

        $order
            ->setInvoicePdfPath('/private/invoice.pdf')
            ->setInvoiceXmlPath('/private/invoice.xml')
            ->setInvoiceNumber('FAC-2026-0001')
            ->setInvoicedAt(new \DateTimeImmutable('2026-08-01'));

        self::assertTrue($service->resendInvoiceIssued($order));
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getInvoiceEmailSentAt());
    }

    public function testStatusChangedSendsOnlySupportedStatusesAndAvoidsDuplicatesUnlessForced(): void
    {
        $order = $this->order([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(3))->method('send')->with(self::callback(static function (Email $email): bool {
            return str_contains((string) $email->getSubject(), 'Commande');
        }));

        $orderEntityManager = $this->createMock(EntityManagerInterface::class);
        $orderEntityManager->expects(self::exactly(3))->method('flush');
        $eventEntityManager = $this->createMock(EntityManagerInterface::class);
        $eventEntityManager->expects(self::exactly(3))->method('persist');
        $eventEntityManager->expects(self::exactly(3))->method('flush');

        $service = $this->service($mailer, $orderEntityManager, $eventEntityManager);

        self::assertFalse($service->sendStatusChangedIfNeeded($order, Order::STATUS_PENDING, Order::STATUS_CONFIRMED));
        self::assertTrue($service->sendStatusChangedIfNeeded($order, Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED));
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getStatusDeliveredEmailSentAt());
        self::assertFalse($service->sendStatusChangedIfNeeded($order, Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED));
        self::assertTrue($service->resendStatusChanged($order, Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED));
        self::assertTrue($service->resendStatusChanged($order, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED));
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getStatusCancelledEmailSentAt());
    }

    private function service(MailerInterface $mailer, EntityManagerInterface $orderEntityManager, EntityManagerInterface $eventEntityManager): OrderNotificationEmailService
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->method('findActiveOneByScenarioKey')->willReturn(null);
        $quotes = $this->getMockBuilder(QuoteRepository::class)->disableOriginalConstructor()->getMock();
        $quotes->method('findOneBy')->willReturn(null);

        return new OrderNotificationEmailService(
            new OrderPersistence($orderEntityManager),
            new OrderNotificationContentProvider($templates, $quotes, 'https://front.example.test'),
            $mailer,
            new OrderEventLogger(new OrderEventPersistence($eventEntityManager)),
            $this->notifier(),
            'noreply@example.test',
        );
    }

    /** @param list<string> $preferences */
    private function order(array $preferences): Order
    {
        $user = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed')->setCommunicationPreferences($preferences);
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        $order = (new Order('ORD-EMAIL-1', $user))
            ->setTotalPriceCents(12345)
            ->setStatus(Order::STATUS_CONFIRMED);
        $this->setId($order, 51);

        return $order;
    }

    private function notifier(): UserCommunicationNotifier
    {
        return new UserCommunicationNotifier(
            $this->notificationRepository(),
            new DoctrineUnitOfWork($this->entityManager()),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.test',
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
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function notificationRepository(): AccountNotificationEventRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new AccountNotificationEventRepository($registry);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
