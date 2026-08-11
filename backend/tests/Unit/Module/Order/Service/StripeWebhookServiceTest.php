<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Application\Handler\OrderStripeWebhookHandler;
use App\Module\Order\Application\Handler\RefundStripeWebhookHandler;
use App\Module\Order\Application\Handler\TrainingStripeWebhookHandler;
use App\Module\Order\Application\Mapper\StripeWebhookVerifier;
use App\Module\Order\Application\Port\StripeWebhookEventPersistencePort;
use App\Module\Order\Application\Port\StripeWebhookEventRepositoryPort;
use App\Module\Order\Application\Workflow\StripeWebhookService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use PHPUnit\Framework\TestCase;

final class StripeWebhookServiceTest extends TestCase
{
    public function testVerifierAcceptsValidSignatureAndRejectsInvalidVariants(): void
    {
        $payload = json_encode([
            'id' => 'evt_valid',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_valid']],
        ], JSON_THROW_ON_ERROR);
        $verifier = new StripeWebhookVerifier('whsec_primary', '');
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_primary');

        self::assertSame('evt_valid', $verifier->verifyAndDecode($payload, sprintf('t=%d,v1=%s', $timestamp, $signature))['id']);

        try {
            $verifier->verifyAndDecode($payload, null);
            self::fail('Expected missing signature exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Signature Stripe manquante.', $exception->getMessage());
        }

        try {
            $verifier->verifyAndDecode($payload, sprintf('t=%d,v1=%s', $timestamp - 3600, $signature));
            self::fail('Expected expired signature exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Signature Stripe expirée.', $exception->getMessage());
        }

        try {
            $verifier->verifyAndDecode($payload, sprintf('t=%d,v1=%s', $timestamp, str_repeat('0', 64)));
            self::fail('Expected invalid signature exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Signature Stripe invalide.', $exception->getMessage());
        }
    }

    public function testServiceReturnsDuplicateForAlreadyProcessedEvent(): void
    {
        $processed = new StripeWebhookEvent('evt_duplicate', 'checkout.session.completed');
        $processed->markProcessed();
        [$payload, $signatureHeader] = $this->signedPayloadAndHeader([
            'id' => 'evt_duplicate',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_duplicate']],
        ]);

        $service = new StripeWebhookService(
            new StripeWebhookVerifier('whsec_test', ''),
            $this->unusedOrderHandler(),
            $this->unusedTrainingHandler(),
            $this->unusedRefundHandler(),
            new class($processed) implements StripeWebhookEventRepositoryPort {
                public function __construct(private readonly StripeWebhookEvent $event)
                {
                }

                public function findOneByStripeEventId(string $eventId): ?StripeWebhookEvent
                {
                    return 'evt_duplicate' === $eventId ? $this->event : null;
                }
            },
            new class implements StripeWebhookEventPersistencePort {
                public function save(StripeWebhookEvent $event): void
                {
                    throw new \LogicException('save() should not be called for an already processed duplicate.');
                }

                public function flush(): void
                {
                    throw new \LogicException('flush() should not be called for an already processed duplicate.');
                }
            },
        );

        self::assertSame([
            'type' => 'checkout.session.completed',
            'eventId' => 'evt_duplicate',
            'duplicate' => true,
        ], $service->handle($payload, $signatureHeader));
    }

    public function testServicePersistsUnknownEventAndMarksItProcessed(): void
    {
        $repository = new InMemoryStripeWebhookEventRepository();
        $persistence = new InMemoryStripeWebhookEventPersistence($repository);
        [$payload, $signatureHeader] = $this->signedPayloadAndHeader([
            'id' => 'evt_unknown',
            'type' => 'payment_link.created',
            'data' => ['object' => ['id' => 'plink_1']],
        ]);

        $service = new StripeWebhookService(
            new StripeWebhookVerifier('whsec_test', ''),
            $this->unusedOrderHandler(),
            $this->unusedTrainingHandler(),
            $this->unusedRefundHandler(),
            $repository,
            $persistence,
        );

        self::assertSame([
            'eventId' => 'evt_unknown',
            'type' => 'payment_link.created',
            'sessionId' => 'plink_1',
        ], $service->handle($payload, $signatureHeader));
        self::assertTrue($repository->findOneByStripeEventId('evt_unknown')?->isProcessed());
        self::assertSame(2, $persistence->flushes);
    }

    public function testServiceMarksFailureThenAllowsRetryForSameEvent(): void
    {
        $repository = new InMemoryStripeWebhookEventRepository();
        $persistence = new InMemoryStripeWebhookEventPersistence($repository);
        [$payload, $signatureHeader] = $this->signedPayloadAndHeader([
            'id' => 'evt_retry',
            'type' => 'refund.updated',
            'data' => ['object' => ['id' => 're_retry', 'status' => 'succeeded', 'metadata' => ['refund_request_id' => 1]]],
        ]);
        $refund = new RefundRequest(
            new Order('ORD-REFUND-1', new User('refund@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female')),
            1200,
        );
        $this->setId($refund, 1);
        $flushes = new class implements UnitOfWork {
            public int $count = 0;

            public function persist(object $entity): void
            {
            }

            public function remove(object $entity): void
            {
            }

            public function flush(): void
            {
                ++$this->count;
            }
        };
        $transactions = new class implements TransactionManager {
            private int $attempt = 0;

            public function transactional(\Closure $operation): mixed
            {
                ++$this->attempt;
                if (1 === $this->attempt) {
                    throw new \RuntimeException('temporary webhook failure');
                }

                return $operation();
            }
        };
        $refundHandler = new RefundStripeWebhookHandler(
            new class($refund) implements \App\Module\Order\Application\Port\RefundRequestRepositoryPort {
                public function __construct(private readonly RefundRequest $refund)
                {
                }

                public function find(mixed $id, \App\Shared\Application\LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
                {
                    return 1 === (int) $id ? $this->refund : null;
                }

                public function findForUpdate(int $id): ?RefundRequest
                {
                    return 1 === $id ? $this->refund : null;
                }

                public function findBy(array $criteria, array $orderBy = null, ?int $limit = null, ?int $offset = null): array
                {
                    return [];
                }

                public function count(array $criteria = []): int
                {
                    return 0;
                }
            },
            $flushes,
            $transactions,
        );

        $service = new StripeWebhookService(
            new StripeWebhookVerifier('whsec_test', ''),
            $this->unusedOrderHandler(),
            $this->unusedTrainingHandler(),
            $refundHandler,
            $repository,
            $persistence,
        );

        try {
            $service->handle($payload, $signatureHeader);
            self::fail('Expected first webhook attempt to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame('temporary webhook failure', $exception->getMessage());
        }

        $failed = $repository->findOneByStripeEventId('evt_retry');
        self::assertInstanceOf(StripeWebhookEvent::class, $failed);
        self::assertSame('failed', $failed->getStatus());

        self::assertSame([
            'eventId' => 'evt_retry',
            'type' => 'refund.updated',
            'refundId' => 're_retry',
            'localRefundId' => 1,
        ], $service->handle($payload, $signatureHeader));
        self::assertTrue($repository->findOneByStripeEventId('evt_retry')?->isProcessed());
    }

    private function unusedOrderHandler(): OrderStripeWebhookHandler
    {
        /** @var OrderStripeWebhookHandler $handler */
        $handler = (new \ReflectionClass(OrderStripeWebhookHandler::class))->newInstanceWithoutConstructor();

        return $handler;
    }

    private function unusedTrainingHandler(): TrainingStripeWebhookHandler
    {
        /** @var TrainingStripeWebhookHandler $handler */
        $handler = (new \ReflectionClass(TrainingStripeWebhookHandler::class))->newInstanceWithoutConstructor();

        return $handler;
    }

    private function unusedRefundHandler(): RefundStripeWebhookHandler
    {
        /** @var RefundStripeWebhookHandler $handler */
        $handler = (new \ReflectionClass(RefundStripeWebhookHandler::class))->newInstanceWithoutConstructor();

        return $handler;
    }

    /**
     * @param array<string, mixed> $event
     *
     * @return array{0:string,1:string}
     */
    private function signedPayloadAndHeader(array $event): array
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        return [$payload, sprintf('t=%d,v1=%s', $timestamp, $signature)];
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}

final class InMemoryStripeWebhookEventRepository implements StripeWebhookEventRepositoryPort
{
    /** @var array<string, StripeWebhookEvent> */
    private array $events = [];

    public function findOneByStripeEventId(string $eventId): ?StripeWebhookEvent
    {
        return $this->events[$eventId] ?? null;
    }

    public function store(StripeWebhookEvent $event): void
    {
        $this->events[$event->getStripeEventId()] = $event;
    }
}

final class InMemoryStripeWebhookEventPersistence implements StripeWebhookEventPersistencePort
{
    public int $flushes = 0;

    public function __construct(private readonly InMemoryStripeWebhookEventRepository $repository)
    {
    }

    public function save(StripeWebhookEvent $event): void
    {
        $this->repository->store($event);
    }

    public function flush(): void
    {
        ++$this->flushes;
    }
}
