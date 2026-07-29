<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Order\Controller\ListOrderMetadataController;
use App\Module\Admin\Quote\Controller\ListQuoteMetadataController;
use App\Module\Audit\Entity\AuditType;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\Audit\Service\AuditPersistence;
use App\Module\BetaTest\Entity\BugReport;
use App\Module\BetaTest\Service\BugReportActivityLogger;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderEvent;
use App\Module\Order\Message\OrderCreatedMessage;
use App\Module\Order\Message\OrderStatusChangedMessage;
use App\Module\Order\Service\OrderEventPersistence;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteNumberGenerator;
use App\Module\TradeIn\Controller\ListTradeInMetadataController;
use App\Module\TradeIn\Service\TradeInMetadataFormatter;
use App\Module\TradeIn\Service\TradeInNumberGenerator;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiValidationException;
use App\Shared\Http\ExternalServiceException;
use App\Shared\Http\InvalidJsonPayloadException;
use App\Shared\Http\RateLimited;
use App\Shared\Mail\MailDeliveryException;
use App\Shared\Persistence\DoctrinePersistence;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class TrivialClassesCoverageTest extends TestCase
{
    public function testOrderMessagesExposePayload(): void
    {
        $created = new OrderCreatedMessage(10, 'ORD-10', 5);
        self::assertSame(10, $created->orderId);
        self::assertSame('ORD-10', $created->orderNumber);
        self::assertSame(5, $created->userId);

        $changed = new OrderStatusChangedMessage(10, 'ORD-10', 'pending', 'confirmed');
        self::assertSame('pending', $changed->oldStatus);
        self::assertSame('confirmed', $changed->newStatus);
    }

    public function testHttpAndMailExceptionsExposeMetadata(): void
    {
        $validation = new ApiValidationException('Invalid', ['field required']);
        self::assertSame('Invalid', $validation->getMessage());
        self::assertSame(['field required'], $validation->details);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $validation->statusCode);

        $external = new ExternalServiceException('downstream failed');
        self::assertSame(Response::HTTP_BAD_GATEWAY, $external->getStatusCode());

        $invalidJson = new InvalidJsonPayloadException('bad json');
        self::assertSame(Response::HTTP_BAD_REQUEST, $invalidJson->getStatusCode());

        $previous = new \RuntimeException('boom');
        $mail = MailDeliveryException::failed('invoice', $previous);
        self::assertSame('Email delivery failed for invoice.', $mail->getMessage());
        self::assertSame($previous, $mail->getPrevious());
    }

    public function testRateLimitedAndTradeInNumberGenerator(): void
    {
        $attribute = new RateLimited('api_public', 2);
        self::assertSame('api_public', $attribute->limiter);
        self::assertSame(2, $attribute->tokens);

        try {
            new RateLimited('api_public', 0);
            self::fail('Expected invalid token exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('The number of consumed tokens must be positive.', $exception->getMessage());
        }

        $reference = (new TradeInNumberGenerator())->generate();
        self::assertMatchesRegularExpression('/^REP-\d{8}-[A-F0-9]{6}$/', $reference);
    }

    public function testSmallControllersReturnSuccessPayloads(): void
    {
        $orderResponse = (new ListOrderMetadataController())();
        self::assertSame(Response::HTTP_OK, $orderResponse->getStatusCode());
        self::assertStringContainsString('"statuses"', (string) $orderResponse->getContent());

        $quoteResponse = (new ListQuoteMetadataController())();
        self::assertSame(Response::HTTP_OK, $quoteResponse->getStatusCode());
        self::assertStringContainsString('"brouillon"', mb_strtolower((string) $quoteResponse->getContent()));

        $tradeInResponse = (new ListTradeInMetadataController(new TradeInMetadataFormatter()))();
        self::assertSame(Response::HTTP_OK, $tradeInResponse->getStatusCode());
        self::assertStringContainsString('"paymentMethods"', (string) $tradeInResponse->getContent());
    }

    public function testSmallPersistenceHelpersDelegateToEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('persist');
        $entityManager->expects(self::exactly(2))->method('flush');

        $auditPersistence = new AuditPersistence($entityManager);
        $orderPersistence = new OrderEventPersistence($entityManager);

        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $auditPersistence->save(new AuditRequest('AUD-1', $user, AuditType::TECHNICAL, 'https://example.com', 'Description'));
        $orderPersistence->save(new OrderEvent(new Order('ORD-1', $user), 'created', 'Body', 9, 'Admin'));
    }

    public function testQuoteNumberGeneratorUsesCurrentYearAndRepositoryCount(): void
    {
        $repository = $this->createMock(QuoteRepository::class);
        $repository->expects(self::once())
            ->method('countForYear')
            ->with((int) gmdate('Y'))
            ->willReturn(12);

        $number = (new QuoteNumberGenerator($repository))->generate();
        self::assertSame(sprintf('DEV-%s-0013', gmdate('Y')), $number);
    }

    public function testBugReportActivityLoggerPersistsActivity(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static fn (object $activity): bool => $activity instanceof \App\Module\BetaTest\Entity\BugReportActivity
                && 'status_changed' === $activity->getAction()
                && 'open' === $activity->getFromValue()
                && 'closed' === $activity->getToValue()
                && 'done' === $activity->getMessage()));

        $logger = new BugReportActivityLogger(new DoctrinePersistence($entityManager));
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $report = new BugReport($user, null, 'Bug', 'Body', null, null, 'low', '/beta');

        $logger->log($report, $user, 'status_changed', 'open', 'closed', 'done');
    }
}
