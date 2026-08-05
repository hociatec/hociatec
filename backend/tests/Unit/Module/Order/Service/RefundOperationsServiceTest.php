<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsEmailLogFormatter;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Admin\Application\Operations\Workflow\RefundOperationsService;
use App\Module\Order\Application\DTO\RefundProcessData;
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
use App\Shared\Application\TransactionManager;
use App\Shared\Application\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RefundOperationsServiceTest extends TestCase
{
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
            $refunds,
            $this->unusedOrders(),
            $this->unusedPayments(),
            $stripe,
            new OrderEventLogger(new OrderEventPersistence($entityManager)),
            new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager),
            new class implements TransactionManager {
                public function transactional(\Closure $operation): mixed
                {
                    return $operation();
                }
            },
            new AdminOperationsFormatter(new AdminOperationsEmailLogFormatter($this->unusedOrders(), $this->unusedOrderEvents())),
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

    private function user(): User
    {
        return new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0600000000', 'female');
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
