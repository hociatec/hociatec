<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Admin\Application\Operations\Projection\AdminOperationsEmailLogFormatter;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Workflow\RefundOperationsService;
use App\Module\Order\Application\DTO\RefundCreateData;
use App\Module\Order\Application\DTO\RefundProcessData;
use App\Module\Order\Application\DTO\RefundUpdateData;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Order\Application\Port\StripeRefundClient;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Infrastructure\Persistence\OrderEventPersistence;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;
use App\Shared\Application\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RefundOperationsServiceTest extends TestCase
{
    public function testListCountCreateAndUpdateRefunds(): void
    {
        $user = $this->user();
        $order = new Order('ORD-24', $user);
        $this->assignId($order, 24);
        $refund = new RefundRequest($order, 2500, $user);
        $this->assignId($refund, 42);

        $refunds = new class($refund) implements RefundRequestRepositoryPort {
            public function __construct(private readonly RefundRequest $refund)
            {
            }

            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return 42 === $id ? $this->refund : null;
            }

            public function findForUpdate(int $id): ?RefundRequest
            {
                return null;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [$this->refund];
            }

            public function count(array $criteria): int
            {
                return 1;
            }
        };
        $orders = new class($order) implements OrderRepositoryPort {
            public function __construct(private readonly Order $order)
            {
            }

            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Order
            {
                return 24 === $id ? $this->order : null;
            }

            public function findForUpdate(int $id): ?Order
            {
                return null;
            }

            public function countForYear(int $year): int
            {
                return 0;
            }

            public function countInvoicedForYear(int $year): int
            {
                return 0;
            }

            public function hasActiveForUser(User $user): bool
            {
                return false;
            }

            public function findByUser(User $user, int $limit = 20, int $offset = 0): array
            {
                return [];
            }

            public function countByUser(User $user): int
            {
                return 0;
            }

            public function findRecentForAdmin(int $limit = 8): array
            {
                return [];
            }

            public function findPendingPaymentForAdmin(int $limit = 10): array
            {
                return [];
            }

            public function findFulfillmentQueue(int $limit = 30): array
            {
                return [];
            }

            public function findForAdminList(?string $status, ?string $health, int $limit, int $offset): array
            {
                return [];
            }

            public function countForAdminList(?string $status, ?string $health): int
            {
                return 0;
            }

            public function getSummaryBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
            {
                return ['count' => 0, 'totalCents' => 0];
            }

            public function getStatusCounts(): array
            {
                return [];
            }

            public function countWithOperationalIssues(): int
            {
                return 0;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [];
            }
        };

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(RefundRequest::class));
        $entityManager->expects(self::exactly(2))->method('flush');

        $service = new RefundOperationsService(
            new \App\Module\Admin\Application\Operations\Workflow\RefundOperationPorts(
                $refunds,
                $orders,
                $this->unusedPayments(),
                new class implements StripeRefundClient {
                    public function createRefund(array $payload, ?string $idempotencyKey = null): array
                    {
                        return [];
                    }
                },
            ),
            new OrderEventLogger(new OrderEventPersistence($entityManager)),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed
                {
                    return $operation();
                }
            },
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter($orders, $this->unusedOrderEvents()),
                \App\Tests\Support\OrderFormatterFactory::create(),
            ),
        );

        self::assertCount(1, $service->list());
        self::assertSame(1, $service->count());

        $created = $service->create(new RefundCreateData(24, null, '  Client request  ', '  note  ', 12, 'eur'), $user);
        self::assertSame(24, $created['order']['id']);
        self::assertSame('requested', $created['status']);
        self::assertSame(12, $created['paymentId']);
        self::assertSame('EUR', $created['currencyCode']);

        $updated = $service->update(42, new RefundUpdateData(RefundRequest::STATUS_APPROVED, '  re_approved  ', '  updated note  '));
        self::assertSame('approved', $updated['status']);
        self::assertSame('re_approved', $updated['stripeRefundId']);
        self::assertSame('updated note', $updated['internalNotes']);
    }

    public function testCreateAndUpdateFailForMissingOrInvalidRefundResources(): void
    {
        $user = $this->user();
        $refunds = new class implements RefundRequestRepositoryPort {
            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return null;
            }

            public function findForUpdate(int $id): ?RefundRequest
            {
                return null;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [];
            }

            public function count(array $criteria): int
            {
                return 0;
            }
        };
        $orders = $this->unusedOrders();
        $service = new RefundOperationsService(
            new \App\Module\Admin\Application\Operations\Workflow\RefundOperationPorts(
                $refunds,
                $orders,
                $this->unusedPayments(),
                new class implements StripeRefundClient {
                    public function createRefund(array $payload, ?string $idempotencyKey = null): array
                    {
                        return [];
                    }
                },
            ),
            new OrderEventLogger(new OrderEventPersistence($this->createMock(EntityManagerInterface::class))),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($this->createMock(EntityManagerInterface::class)),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed
                {
                    return $operation();
                }
            },
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter($orders, $this->unusedOrderEvents()),
                \App\Tests\Support\OrderFormatterFactory::create(),
            ),
        );

        try {
            $service->create(new RefundCreateData(999, 1000, null, null, null, 'EUR'), $user);
            self::fail('Expected missing order exception.');
        } catch (OperationsResourceNotFoundException $exception) {
            self::assertSame('Commande introuvable.', $exception->getMessage());
        }

        try {
            $service->update(999, new RefundUpdateData(RefundRequest::STATUS_APPROVED, null, null));
            self::fail('Expected missing refund exception.');
        } catch (OperationsResourceNotFoundException $exception) {
            self::assertSame('Remboursement introuvable.', $exception->getMessage());
        }

        try {
            $service->update(999, new RefundUpdateData('not-a-status', null, null));
            self::fail('Expected invalid status exception.');
        } catch (OperationsResourceNotFoundException $exception) {
            self::assertSame('Remboursement introuvable.', $exception->getMessage());
        }
    }

    public function testProcessStripeUsesStableRefundIdempotencyKey(): void
    {
        $user = $this->user();
        $order = new Order('ORD-42', $user);
        $refund = new RefundRequest($order, 2500, $user);
        $this->assignId($refund, 42);

        $refunds = new class($refund) implements RefundRequestRepositoryPort {
            public bool $locked = false;

            public function __construct(private readonly RefundRequest $refund)
            {
            }

            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return 42 === $id ? $this->refund : null;
            }

            public function findForUpdate(int $id): ?RefundRequest
            {
                $this->locked = true;

                return 42 === $id ? $this->refund : null;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [$this->refund];
            }

            public function count(array $criteria): int
            {
                return 1;
            }
        };
        $stripe = new class implements StripeRefundClient {
            /** @var array<string, mixed> */
            public array $payload = [];
            public ?string $idempotencyKey = null;

            public function createRefund(array $payload, ?string $idempotencyKey = null): array
            {
                $this->payload = $payload;
                $this->idempotencyKey = $idempotencyKey;

                return ['id' => 're_42'];
            }
        };

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::exactly(3))->method('flush');

        $service = new RefundOperationsService(
            new \App\Module\Admin\Application\Operations\Workflow\RefundOperationPorts(
                $refunds,
                $this->unusedOrders(),
                $this->unusedPayments(),
                $stripe,
            ),
            new OrderEventLogger(new OrderEventPersistence($entityManager)),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed
                {
                    return $operation();
                }
            },
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter($this->unusedOrders(), $this->unusedOrderEvents()),
                \App\Tests\Support\OrderFormatterFactory::create(),
            ),
        );

        $result = $service->processStripe(42, new RefundProcessData('REMBOURSER', 'pi_42'), $user);

        self::assertTrue($refunds->locked);
        self::assertSame('refund_request:42', $stripe->idempotencyKey);
        self::assertSame('pi_42', $stripe->payload['payment_intent']);
        self::assertSame('42', $stripe->payload['metadata[refund_request_id]']);
        self::assertSame('re_42', $refund->getStripeRefundId());
        self::assertSame(RefundRequest::STATUS_PROCESSED, $refund->getStatus());
        self::assertSame('re_42', $result['stripeRefund']['id']);
    }

    public function testProcessStripeRejectsAlreadyProcessedZeroAmountAndMissingConfirmation(): void
    {
        $user = $this->user();
        $order = new Order('ORD-10', $user);

        $processedRefund = new RefundRequest($order, 2500, $user);
        $this->assignId($processedRefund, 10);
        $processedRefund->setStripeRefundId('re_done');

        $service = $this->serviceForSingleRefund($processedRefund);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ce remboursement a déjà été traité.');
        $service->processStripe(10, new RefundProcessData('REMBOURSER', 'pi_10'), $user);
    }

    public function testProcessStripeRejectsZeroAmountConfirmationAndMissingPaymentIntent(): void
    {
        $user = $this->user();
        $order = new Order('ORD-11', $user);
        $this->assignId($order, 11);

        $zeroRefund = new RefundRequest($order, 1, $user);
        $this->assignId($zeroRefund, 11);
        $zeroRefund->setAmountCents(0);

        try {
            $this->serviceForSingleRefund($zeroRefund)->processStripe(11, new RefundProcessData('REMBOURSER', 'pi_11'), $user);
            self::fail('Expected zero amount exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Le montant du remboursement doit être supérieur à zéro.', $exception->getMessage());
        }

        $confirmationRefund = new RefundRequest($order, 2500, $user);
        $this->assignId($confirmationRefund, 12);
        try {
            $this->serviceForSingleRefund($confirmationRefund)->processStripe(12, new RefundProcessData('NOPE', 'pi_12'), $user);
            self::fail('Expected confirmation exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Confirmation requise : saisis REMBOURSER pour déclencher Stripe.', $exception->getMessage());
        }

        $missingPaymentIntentRefund = new RefundRequest($order, 2500, $user);
        $this->assignId($missingPaymentIntentRefund, 13);
        try {
            $this->serviceForSingleRefund($missingPaymentIntentRefund)->processStripe(13, new RefundProcessData('REMBOURSER', null), $user);
            self::fail('Expected missing payment intent exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Aucun PaymentIntent Stripe trouvé pour cette commande.', $exception->getMessage());
        }
    }

    public function testProcessStripeUsesRecentOrderPaymentIntentAndHandlesTransactionalFailures(): void
    {
        $user = $this->user();
        $order = new Order('ORD-14', $user);
        $this->assignId($order, 14);
        $refund = new RefundRequest($order, 2500, $user);
        $this->assignId($refund, 14);

        $payment = (new OrderCheckoutSession('tok-14', $user, 'cart-14', 1, 'cs_14', 'https://checkout.test'))
            ->markPaid('pi_recent_14', 'paid', 'checkout.session.completed');

        $service = $this->serviceForSingleRefund(
            $refund,
            payments: new class($payment) implements OrderCheckoutSessionRepositoryPort {
                public function __construct(private readonly OrderCheckoutSession $payment)
                {
                }

                public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?OrderCheckoutSession { return null; }
                public function findOneByStripeSessionId(string $stripeSessionId): ?OrderCheckoutSession { return null; }
                public function findOneByStripePaymentIntentId(string $stripePaymentIntentId): ?OrderCheckoutSession { return null; }
                public function findOneByToken(string $token): ?OrderCheckoutSession { return null; }
                public function findOneByOrderId(int $orderId): ?OrderCheckoutSession { return null; }
                public function findReusableOpenSessionForCart(User $user, string $cartToken): ?OrderCheckoutSession { return null; }
                public function findReusableOpenSessionForOrder(User $user, int $orderId): ?OrderCheckoutSession { return null; }
                public function getStatusCounts(): array { return []; }
                public function countPaidWithoutOrder(): int { return 0; }
                public function findRecentForDashboard(int $limit = 6): array { return []; }
                public function findAttentionItemsForDashboard(int $limit = 6): array { return []; }
                public function findRecentOpen(int $limit = 20): array { return []; }
                public function findRecentByOrderId(int $orderId, int $limit = 5): array { return [$this->payment]; }
                public function findForAdminList(?string $status, string $query, int $limit = 20, int $offset = 0): array { return []; }
                public function countForAdminList(?string $status, string $query): int { return 0; }
                public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array { return []; }
            },
            stripe: new class implements StripeRefundClient {
                /** @var array<string,mixed> */
                public array $payload = [];
                public function createRefund(array $payload, ?string $idempotencyKey = null): array
                {
                    $this->payload = $payload;

                    return ['id' => 're_14'];
                }
            },
        );

        $result = $service->processStripe(14, new RefundProcessData('REMBOURSER', null), $user);
        self::assertSame('re_14', $result['stripeRefund']['id']);
        self::assertSame(RefundRequest::STATUS_PROCESSED, $refund->getStatus());

        $missingLockedRefund = new RefundRequest($order, 2500, $user);
        $this->assignId($missingLockedRefund, 15);
        $missingLockedRefundService = new RefundOperationsService(
            new \App\Module\Admin\Application\Operations\Workflow\RefundOperationPorts(
                new class($missingLockedRefund) implements RefundRequestRepositoryPort {
                    public function __construct(private readonly RefundRequest $refund) {}
                    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object { return 15 === $id ? $this->refund : null; }
                    public function findForUpdate(int $id): ?RefundRequest { return null; }
                    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array { return []; }
                    public function count(array $criteria): int { return 1; }
                },
                $this->unusedOrders(),
                $this->unusedPayments(),
                new class implements StripeRefundClient {
                    public function createRefund(array $payload, ?string $idempotencyKey = null): array { return []; }
                },
            ),
            new OrderEventLogger(new OrderEventPersistence($this->createMock(EntityManagerInterface::class))),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($this->createMock(EntityManagerInterface::class)),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed { return $operation(); }
            },
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter($this->unusedOrders(), $this->unusedOrderEvents()),
                \App\Tests\Support\OrderFormatterFactory::create(),
            ),
        );
        try {
            $missingLockedRefundService->processStripe(15, new RefundProcessData('REMBOURSER', 'pi_15'), $user);
            self::fail('Expected missing locked refund exception.');
        } catch (OperationsResourceNotFoundException $exception) {
            self::assertSame('Remboursement introuvable.', $exception->getMessage());
        }

        $processingRefund = new RefundRequest($order, 2500, $user);
        $this->assignId($processingRefund, 16);
        $processingRefund->setStatus(RefundRequest::STATUS_PROCESSING);
        try {
            $this->serviceForSingleRefund($processingRefund)->processStripe(16, new RefundProcessData('REMBOURSER', 'pi_16'), $user);
            self::fail('Expected in-progress refund exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Ce remboursement est déjà en cours ou a déjà été traité.', $exception->getMessage());
        }
    }

    public function testProcessStripeRestoresPreviousStatusWhenStripeFails(): void
    {
        $user = $this->user();
        $order = new Order('ORD-17', $user);
        $refund = new RefundRequest($order, 2500, $user);
        $this->assignId($refund, 17);
        $refund->setStatus(RefundRequest::STATUS_APPROVED);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('flush');
        $service = new RefundOperationsService(
            new \App\Module\Admin\Application\Operations\Workflow\RefundOperationPorts(
                new class($refund) implements RefundRequestRepositoryPort {
                    public function __construct(private readonly RefundRequest $refund) {}
                    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object { return 17 === $id ? $this->refund : null; }
                    public function findForUpdate(int $id): ?RefundRequest { return 17 === $id ? $this->refund : null; }
                    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array { return []; }
                    public function count(array $criteria): int { return 1; }
                },
                $this->unusedOrders(),
                $this->unusedPayments(),
                new class implements StripeRefundClient {
                    public function createRefund(array $payload, ?string $idempotencyKey = null): array
                    {
                        throw new \App\Shared\Application\Exception\ExternalServiceException('stripe down');
                    }
                },
            ),
            new OrderEventLogger(new OrderEventPersistence($entityManager)),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed { return $operation(); }
            },
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter($this->unusedOrders(), $this->unusedOrderEvents()),
                \App\Tests\Support\OrderFormatterFactory::create(),
            ),
        );

        try {
            $service->processStripe(17, new RefundProcessData('REMBOURSER', 'pi_17'), $user);
            self::fail('Expected Stripe failure.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Stripe a refusé le remboursement.', $exception->getMessage());
            self::assertSame(RefundRequest::STATUS_APPROVED, $refund->getStatus());
        }
    }

    public function testUpdateRejectsInvalidStatusForExistingRefund(): void
    {
        $user = $this->user();
        $order = new Order('ORD-77', $user);
        $refund = new RefundRequest($order, 2500, $user);
        $this->assignId($refund, 77);

        $refunds = new class($refund) implements RefundRequestRepositoryPort {
            public function __construct(private readonly RefundRequest $refund)
            {
            }

            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return 77 === $id ? $this->refund : null;
            }

            public function findForUpdate(int $id): ?RefundRequest
            {
                return null;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [];
            }

            public function count(array $criteria): int
            {
                return 1;
            }
        };

        $service = new RefundOperationsService(
            new \App\Module\Admin\Application\Operations\Workflow\RefundOperationPorts(
                $refunds,
                $this->unusedOrders(),
                $this->unusedPayments(),
                new class implements StripeRefundClient {
                    public function createRefund(array $payload, ?string $idempotencyKey = null): array
                    {
                        return [];
                    }
                },
            ),
            new OrderEventLogger(new OrderEventPersistence($this->createMock(EntityManagerInterface::class))),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($this->createMock(EntityManagerInterface::class)),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed
                {
                    return $operation();
                }
            },
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter($this->unusedOrders(), $this->unusedOrderEvents()),
                \App\Tests\Support\OrderFormatterFactory::create(),
            ),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statut de remboursement invalide.');
        $service->update(77, new RefundUpdateData('not-a-status', null, null));
    }

    private function user(): User
    {
        return new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0600000000', 'female');
    }

    private function serviceForSingleRefund(
        RefundRequest $refund,
        ?OrderCheckoutSessionRepositoryPort $payments = null,
        ?StripeRefundClient $stripe = null,
        ?RefundRequest $refundFindForUpdate = null,
    ): RefundOperationsService {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('flush');
        $entityManager->method('persist');

        $refunds = new class($refund, $refundFindForUpdate ?? $refund) implements RefundRequestRepositoryPort {
            public function __construct(private readonly RefundRequest $refund, private readonly ?RefundRequest $lockedRefund)
            {
            }

            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return $this->refund->getId() === $id ? $this->refund : null;
            }

            public function findForUpdate(int $id): ?RefundRequest
            {
                return $this->refund->getId() === $id ? $this->lockedRefund : null;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [$this->refund];
            }

            public function count(array $criteria): int
            {
                return 1;
            }
        };

        return new RefundOperationsService(
            new \App\Module\Admin\Application\Operations\Workflow\RefundOperationPorts(
                $refunds,
                $this->unusedOrders(),
                $payments ?? $this->unusedPayments(),
                $stripe ?? new class implements StripeRefundClient {
                    public function createRefund(array $payload, ?string $idempotencyKey = null): array
                    {
                        return ['id' => 're_default'];
                    }
                },
            ),
            new OrderEventLogger(new OrderEventPersistence($entityManager)),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed
                {
                    return $operation();
                }
            },
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter($this->unusedOrders(), $this->unusedOrderEvents()),
                \App\Tests\Support\OrderFormatterFactory::create(),
            ),
        );
    }

    private function assignId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }

    private function unusedOrders(): OrderRepositoryPort
    {
        return new class implements OrderRepositoryPort {
            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Order
            {
                return null;
            }

            public function findForUpdate(int $id): ?Order
            {
                return null;
            }

            public function countForYear(int $year): int
            {
                return 0;
            }

            public function countInvoicedForYear(int $year): int
            {
                return 0;
            }

            public function hasActiveForUser(User $user): bool
            {
                return false;
            }

            public function findByUser(User $user, int $limit = 20, int $offset = 0): array
            {
                return [];
            }

            public function countByUser(User $user): int
            {
                return 0;
            }

            public function findRecentForAdmin(int $limit = 8): array
            {
                return [];
            }

            public function findPendingPaymentForAdmin(int $limit = 10): array
            {
                return [];
            }

            public function findFulfillmentQueue(int $limit = 30): array
            {
                return [];
            }

            public function findForAdminList(?string $status, ?string $health, int $limit, int $offset): array
            {
                return [];
            }

            public function countForAdminList(?string $status, ?string $health): int
            {
                return 0;
            }

            public function getSummaryBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
            {
                return ['count' => 0, 'totalCents' => 0];
            }

            public function getStatusCounts(): array
            {
                return [];
            }

            public function countWithOperationalIssues(): int
            {
                return 0;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [];
            }
        };
    }

    private function unusedOrderEvents(): OrderEventRepositoryPort
    {
        return new class implements OrderEventRepositoryPort {
            public function findByOrder(Order $order, string $direction = 'DESC'): array
            {
                return [];
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [];
            }

            public function findIssueEventsGroupedByOrders(array $orders): array
            {
                return [];
            }
        };
    }

    private function unusedPayments(): OrderCheckoutSessionRepositoryPort
    {
        return new class implements OrderCheckoutSessionRepositoryPort {
            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?OrderCheckoutSession
            {
                return null;
            }

            public function findOneByStripeSessionId(string $stripeSessionId): ?OrderCheckoutSession
            {
                return null;
            }

            public function findOneByStripePaymentIntentId(string $stripePaymentIntentId): ?OrderCheckoutSession
            {
                return null;
            }

            public function findOneByToken(string $token): ?OrderCheckoutSession
            {
                return null;
            }

            public function findOneByOrderId(int $orderId): ?OrderCheckoutSession
            {
                return null;
            }

            public function findReusableOpenSessionForCart(User $user, string $cartToken): ?OrderCheckoutSession
            {
                return null;
            }

            public function findReusableOpenSessionForOrder(User $user, int $orderId): ?OrderCheckoutSession
            {
                return null;
            }

            public function getStatusCounts(): array
            {
                return [];
            }

            public function countPaidWithoutOrder(): int
            {
                return 0;
            }

            public function findRecentForDashboard(int $limit = 6): array
            {
                return [];
            }

            public function findAttentionItemsForDashboard(int $limit = 6): array
            {
                return [];
            }

            public function findRecentOpen(int $limit = 20): array
            {
                return [];
            }

            public function findRecentByOrderId(int $orderId, int $limit = 5): array
            {
                return [];
            }

            public function findForAdminList(?string $status, string $query, int $limit = 20, int $offset = 0): array
            {
                return [];
            }

            public function countForAdminList(?string $status, string $query): int
            {
                return 0;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [];
            }
        };
    }
}
