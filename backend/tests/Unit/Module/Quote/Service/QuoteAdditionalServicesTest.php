<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Service;

use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Application\Outbox\SendQuoteCreatedEmailHandler;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Provider\QuoteCreatedEmailContentProvider;
use App\Module\Quote\Application\Workflow\QuoteEmailDeliveryService;
use App\Module\Quote\Application\Workflow\QuoteEmailService;
use App\Module\Quote\Infrastructure\Persistence\QuotePersistence;
use App\Module\Quote\Infrastructure\Pdf\QuotePdfService;
use App\Module\Quote\Application\Mapper\QuoteStatusTranslator;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Outbox\Application\Outbox;
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

final class QuoteAdditionalServicesTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testStatusTranslatorNormalizesCodesLabelsAccentsAndOptions(): void
    {
        self::assertSame('envoyé', QuoteStatusTranslator::toLabel(Quote::STATUS_SENT));
        self::assertSame('custom', QuoteStatusTranslator::toLabel('custom'));
        self::assertSame(Quote::STATUS_ACCEPTED, QuoteStatusTranslator::toCode(' Accepté '));
        self::assertSame(Quote::STATUS_REFUSED, QuoteStatusTranslator::toCode(' REFUSÉ '));
        self::assertSame('', QuoteStatusTranslator::toCode('   '));
        self::assertSame('unknown', QuoteStatusTranslator::toCode(' Unknown '));
        self::assertContains(['value' => Quote::STATUS_DRAFT, 'label' => 'Brouillon'], QuoteStatusTranslator::options());
    }

    public function testWorkflowSaveAndDeleteDelegateToPersistence(): void
    {
        $quote = new Quote('DEV-WF-1');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($quote);
        $entityManager->expects(self::once())->method('remove')->with($quote);
        $entityManager->expects(self::exactly(2))->method('flush');

        $workflow = new QuoteWorkflowService(new QuotePersistence($entityManager));
        $workflow->save($quote);
        $workflow->delete($quote);
    }

    public function testCreatedEmailContentProviderBuildsFallbackContentWithTotalsAndEscapedHtml(): void
    {
        $quote = $this->quote('DEV-HTML-1', 'Ada & Co', 'ada@example.test');
        $this->setEntityId($quote, 77);
        $quote->setValidUntil(new \DateTimeImmutable('2026-08-31'));

        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository->expects(self::once())->method('findActiveOneByScenarioKey')->with('quote_created')->willReturn(null);
        $provider = new QuoteCreatedEmailContentProvider(
            new QuoteCalculator(),
            new EmailTemplateRenderer($repository),
            'https://app.example.test/',
            'contact@example.test',
        );

        $content = $provider->build($quote);

        self::assertStringContainsString('DEV-HTML-1', $content['subject']);
        self::assertStringContainsString('Ada &amp; Co', $content['html']);
        self::assertStringContainsString('120,00 EUR', $content['html']);
        self::assertStringContainsString('31/08/2026', $content['text']);
        self::assertStringContainsString('https://app.example.test/quotes/me/77', $content['text']);
    }

    public function testQuoteEmailServiceValidatesRecipientSendsAndMarksCreatedWhenNeeded(): void
    {
        $quote = $this->quote('DEV-MAIL-1', 'Ada', '  ada@example.test  ');
        $this->setEntityId($quote, 500);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = new QuoteEmailService(
            new QuotePersistence($entityManager),
            new Outbox(new DoctrineUnitOfWork($entityManager)),
            $this->repository(UserRepository::class),
            $this->notifier(),
        );

        $sent = $service->sendCreatedIfNeeded($quote);
        self::assertTrue($sent);
        self::assertNotNull($quote->getCreatedEmailSentAt());
        self::assertFalse($service->sendCreatedIfNeeded($quote));
    }

    public function testQuoteEmailServiceSupportsOverrideRecipientNotificationOnlyAndInvalidRecipients(): void
    {
        $user = new User('member@example.test', 'Member', 'Only', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed')->setCommunicationPreferences(['notification']);
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        $service = new QuoteEmailService(
            new QuotePersistence($this->createMock(EntityManagerInterface::class)),
            new Outbox(new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class))),
            $this->repository(UserRepository::class),
            $this->notifier(),
        );

        $quoteForOverride = $this->quote('DEV-MAIL-2', 'Ada', 'ada@example.test');
        $this->setEntityId($quoteForOverride, 502);
        $override = $service->send($quoteForOverride, ' override@example.test ');
        self::assertSame('override@example.test', $override['to']);
        self::assertSame('outbox', $override['transport']);

        $quoteForMember = $this->quote('DEV-MAIL-3', 'Member', 'member@example.test');
        $this->setEntityId($quoteForMember, 501);
        $notificationOnly = $service->send($quoteForMember);
        self::assertSame('notification_only', $notificationOnly['transport']);

        foreach (['', 'bad-email'] as $recipient) {
            try {
                $service->send($this->quote('DEV-MAIL-4', 'Ada', $recipient));
                self::fail('Invalid recipient did not throw.');
            } catch (\InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testQuoteCreatedEmailOutboxHandlerDeliversCurrentEvent(): void
    {
        $quote = $this->quote('DEV-OUTBOX-1', 'Ada', 'ada@example.test');
        $this->setEntityId($quote, 700);
        $quotes = $this->getMockBuilder(QuoteRepository::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
        $quotes->expects(self::once())->method('find')->with(700)->willReturn($quote);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::callback(static function (\Symfony\Component\Mime\Email $email): bool {
            return 'quote-email-700' === $email->getHeaders()->get('X-Hociatec-Idempotency-Key')?->getBodyAsString();
        }));
        $delivery = new QuoteEmailDeliveryService(
            new QuoteCalculator(),
            $this->pdfService('%PDF-1.4'),
            $mailer,
            $this->createMock(LoggerInterface::class),
            'contact@example.test',
        );
        $handler = new SendQuoteCreatedEmailHandler($quotes, $this->contentProvider(), $delivery);
        $event = new OutboxEvent('quote-email-700', 'quote.created_email_requested', ['quoteId' => 700, 'recipient' => 'ada@example.test']);

        self::assertTrue($handler->supports($event));
        $handler->handle($event);
    }

    private function quote(string $number, string $customerName, string $customerEmail): Quote
    {
        $quote = (new Quote($number))
            ->setCustomerName($customerName)
            ->setCustomerEmail($customerEmail);
        $item = (new QuoteItem('Audit', 10000))->setQuantity(1)->setVatRateBps(2000);
        $quote->addItem($item);

        return $quote;
    }

    private function contentProvider(): QuoteCreatedEmailContentProvider
    {
        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository->method('findActiveOneByScenarioKey')->willReturn(null);

        return new QuoteCreatedEmailContentProvider(
            new QuoteCalculator(),
            new EmailTemplateRenderer($repository),
            'https://app.example.test',
            'contact@example.test',
        );
    }

    /** @param array{to:string, attachmentIncluded:bool, transport:string} $result */
    private function deliveryService(array $result): QuoteEmailDeliveryService
    {
        $pdf = $this->createMock(QuotePdfService::class);
        if ($result['attachmentIncluded']) {
            $pdf->method('render')->willReturn('%PDF-1.4');
        } else {
            $pdf->method('render')->willThrowException(new \RuntimeException('pdf disabled'));
        }

        return new QuoteEmailDeliveryService(
            new QuoteCalculator(),
            $pdf,
            $this->createMock(MailerInterface::class),
            $this->createMock(LoggerInterface::class),
            'contact@example.test',
        );
    }

    private function pdfService(string $pdf): QuotePdfService
    {
        $service = $this->createMock(QuotePdfService::class);
        $service->method('render')->willReturn($pdf);

        return $service;
    }

    private function notifier(): UserCommunicationNotifier
    {
        return new UserCommunicationNotifier(
            $this->repository(AccountNotificationEventRepository::class),
            new DoctrineUnitOfWork($this->entityManager()),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'contact@example.test',
            'https://app.example.test',
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
            $entityManager->getClassMetadata(OutboxEvent::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     *
     * @return T
     */
    private function repository(string $repositoryClass): object
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new $repositoryClass($registry);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
