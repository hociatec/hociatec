<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Repository;

use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Infrastructure\Repository\OrderCheckoutSessionRepository;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class OrderCheckoutSessionRepositoryTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testRepositoryQueriesCoverLookupCountsAndDashboardSelections(): void
    {
        $entityManager = $this->entityManager();
        $userA = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $userA->setPassword('hashed');
        $userB = new User('grace@example.test', 'Grace', 'Hopper', new \DateTimeImmutable('1985-12-09'), '0102030406', 'female');
        $userB->setPassword('hashed');
        $entityManager->persist($userA);
        $entityManager->persist($userB);

        $openOld = $this->session('tok-open-old', $userA, 'cart-a-old', 'sess-open-old')
            ->setStatus(OrderCheckoutSession::STATUS_OPEN)
            ->setExpiresAt(new \DateTimeImmutable('2026-07-30T09:00:00+00:00'));
        $openNew = $this->session('tok-open-new', $userA, 'cart-a', 'sess-open-new')
            ->setStatus(OrderCheckoutSession::STATUS_OPEN)
            ->setStripePaymentIntentId('pi-open-new');
        $paidWithoutOrder = $this->session('tok-paid-missing', $userA, 'cart-b', 'sess-paid-missing')
            ->markPaid('pi-paid-missing', 'paid', 'checkout.session.completed');
        $paidWithOrder = $this->session('tok-paid-order', $userA, 'cart-c', 'sess-paid-order')
            ->markPaid('pi-paid-order', 'paid', 'checkout.session.completed')
            ->setOrderId(42);
        $failed = $this->session('tok-failed', $userA, 'cart-d', 'sess-failed')
            ->markFailed('pi-failed', 'requires_payment_method', 'payment_intent.payment_failed', 'card_declined', 'Carte refusée');
        $expired = $this->session('tok-expired', $userB, 'cart-z', 'sess-expired')
            ->markExpired('checkout.session.expired');
        $reusableOrder = $this->session('tok-order-open', $userA, 'cart-order', 'sess-order-open')
            ->setOrderId(77)
            ->setStatus(OrderCheckoutSession::STATUS_OPEN);

        $this->setCreatedAt($openOld, '2026-07-20T10:00:00+00:00');
        $this->setCreatedAt($openNew, '2026-07-20T11:00:00+00:00');
        $this->setCreatedAt($paidWithoutOrder, '2026-07-20T12:00:00+00:00');
        $this->setCreatedAt($paidWithOrder, '2026-07-20T13:00:00+00:00');
        $this->setCreatedAt($failed, '2026-07-20T14:00:00+00:00');
        $this->setCreatedAt($expired, '2026-07-20T15:00:00+00:00');
        $this->setCreatedAt($reusableOrder, '2026-07-20T16:00:00+00:00');

        foreach ([$openOld, $openNew, $paidWithoutOrder, $paidWithOrder, $failed, $expired, $reusableOrder] as $session) {
            $entityManager->persist($session);
        }
        $entityManager->flush();

        $repository = $this->repository($entityManager);

        self::assertSame($openNew->getId(), $repository->findOneByToken('tok-open-new')?->getId());
        self::assertSame($openNew->getId(), $repository->findOneByStripePaymentIntentId('pi-open-new')?->getId());
        self::assertSame($failed->getId(), $repository->findOneByStripeSessionId('sess-failed')?->getId());
        self::assertNull($repository->findOneByToken('missing'));

        self::assertSame($openNew->getId(), $repository->findReusableOpenSessionForCart($userA, 'cart-a')?->getId());
        self::assertSame($reusableOrder->getId(), $repository->findReusableOpenSessionForOrder($userA, 77)?->getId());
        self::assertNull($repository->findReusableOpenSessionForOrder($userA, 404));

        self::assertSame([
            OrderCheckoutSession::STATUS_OPEN => 3,
            OrderCheckoutSession::STATUS_PAID => 2,
            OrderCheckoutSession::STATUS_EXPIRED => 1,
            OrderCheckoutSession::STATUS_FAILED => 1,
        ], $repository->getStatusCounts());
        self::assertSame(1, $repository->countPaidWithoutOrder());

        $recent = $repository->findRecentForDashboard(3);
        self::assertCount(3, $recent);
        self::assertSame(
            ['tok-order-open', 'tok-expired', 'tok-failed'],
            array_map(static fn (OrderCheckoutSession $session): string => $session->getToken(), $recent),
        );

        $attention = $repository->findAttentionItemsForDashboard(5);
        self::assertSame(
            ['tok-expired', 'tok-failed', 'tok-paid-missing'],
            array_map(static fn (OrderCheckoutSession $session): string => $session->getToken(), $attention),
        );
    }

    private function session(string $token, User $user, string $cartToken, string $stripeSessionId): OrderCheckoutSession
    {
        return (new OrderCheckoutSession(
            $token,
            $user,
            $cartToken,
            12,
            $stripeSessionId,
            'https://stripe.test/checkout/'.$token
        ))
            ->setItemsPayload([['sku' => 'SKU-'.$token, 'quantity' => 1]])
            ->setSubtotalPriceCents(1000)
            ->setDiscountAmountCents(100)
            ->setTotalPriceCents(900);
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
            $entityManager->getClassMetadata(OrderCheckoutSession::class),
        ]);

        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function repository(EntityManager $entityManager): OrderCheckoutSessionRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new OrderCheckoutSessionRepository($registry);
    }

    private function setCreatedAt(OrderCheckoutSession $session, string $date): void
    {
        $reflection = new \ReflectionObject($session);
        $value = new \DateTimeImmutable($date);
        $reflection->getProperty('createdAt')->setValue($session, $value);
        $reflection->getProperty('updatedAt')->setValue($session, $value);
    }
}
