<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing;

use App\Module\Marketing\Application\Provider\MarketingRecipientContextProvider;
use App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingRecipientContextQuery;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;

final class MarketingRecipientContextProviderTest extends MarketingIntegrationTestCase
{
    public function testRecipientContextProviderCoversEmptyOrderFallbacks(): void
    {
        $em = $this->entityManager();
        $user = $this->user('empty@example.com', []);
        $em->persist($user);
        $em->flush();

        $context = (new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($em), 'https://front.example.test/'))->provide($user);

        self::assertSame('0', $context['order_count']);
        self::assertSame('', $context['last_order_date']);
        self::assertSame('', $context['last_order_number']);
        self::assertSame('', $context['days_since_last_order']);
        self::assertSame('https://front.example.test', $context['app_frontend_url']);
    }

    public function testRecipientContextProviderIncludesOrderStatsAndPendingReviews(): void
    {
        $em = $this->entityManager();
        $user = $this->user('buyer@example.com', []);
        $order = (new Order('ORD-MKT-1', $user))->setTotalPriceCents(12345);
        $order->addItem(new OrderItem('Laptop', 'SKU-1', 10000, 1));
        $em->persist($user);
        $em->persist($order);
        $em->flush();

        $context = (new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($em), 'https://front.example.test'))->provide($user);

        self::assertSame('1', $context['order_count']);
        self::assertSame('123,45', $context['total_spent_eur']);
        self::assertSame('ORD-MKT-1', $context['last_order_number']);
        self::assertSame('1', $context['pending_reviews_count']);
    }

    public function testRecipientContextProviderFormatsDateTimeOrderStats(): void
    {
        $user = $this->user('dated@example.com', []);
        $order = new Order('ORD-DATE-1', $user);
        $lastOrderAt = new \DateTimeImmutable('2026-07-15T10:00:00+00:00');
        $queries = [
            $this->query(singleResult: ['ordersCount' => 2, 'lastOrderAt' => $lastOrderAt, 'totalSpentCents' => 9900]),
            $this->query(oneOrNullResult: $order),
            $this->query(singleScalarResult: 0),
        ];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(3))->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            $this->queryBuilder($queries[0]),
            $this->queryBuilder($queries[1]),
            $this->queryBuilder($queries[2]),
        );

        $context = (new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($entityManager), 'https://front.example.test'))->provide($user);

        self::assertSame('15/07/2026', $context['last_order_date']);
        self::assertSame('ORD-DATE-1', $context['last_order_number']);
        self::assertNotSame('', $context['days_since_last_order']);
    }
}
