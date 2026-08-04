<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification\Service;

use App\Module\Notification\Application\Service\AccountNotificationFormatter;
use App\Module\Notification\Application\Service\PendingReviewNotificationProvider;
use App\Module\Order\Infrastructure\Repository\OrderItemRepository;
use App\Module\Rating\Application\Service\PendingReviewResolver;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class PendingReviewNotificationProviderTest extends TestCase
{
    public function testProviderReturnsEmptyArrayWithoutPendingReviews(): void
    {
        $resolver = $this->resolver([]);
        $provider = new PendingReviewNotificationProvider($resolver, new AccountNotificationFormatter());

        self::assertSame([], $provider->provide($this->user(), new \DateTimeImmutable('2026-08-01T12:00:00+00:00')));
    }

    public function testProviderBuildsStablePluralizedNotificationFromPendingReviews(): void
    {
        $resolver = $this->resolver([
            ['orderId' => 42, 'orderItemId' => 9],
            ['orderId' => 42, 'orderItemId' => 3],
        ]);
        $provider = new PendingReviewNotificationProvider($resolver, new AccountNotificationFormatter());

        $payload = $provider->provide($this->user(), new \DateTimeImmutable('2026-08-01T12:00:00+00:00'));

        self::assertCount(1, $payload);
        self::assertSame('reviews:3,9', $payload[0]['key']);
        self::assertSame('2 avis produits à laisser', $payload[0]['label']);
        self::assertSame('Vous avez 2 avis produits à laisser sur une commande livrée.', $payload[0]['message']);
        self::assertSame('/orders/42', $payload[0]['to']);
        self::assertSame('pending_reviews', $payload[0]['type']);
    }

    public function testProviderFallsBackToMyOrdersWhenOrderIdIsMissing(): void
    {
        $provider = new PendingReviewNotificationProvider(
            $this->resolver([['orderItemId' => 5]]),
            new AccountNotificationFormatter(),
        );

        $payload = $provider->provide($this->user(), new \DateTimeImmutable('2026-08-01T12:00:00+00:00'));

        self::assertSame('reviews:5', $payload[0]['key']);
        self::assertSame('1 avis produit à laisser', $payload[0]['label']);
        self::assertSame('/orders/me', $payload[0]['to']);
    }

    /** @param list<array<string, mixed>> $items */
    private function resolver(array $items): PendingReviewResolver
    {
        $resolver = $this->getMockBuilder(PendingReviewResolver::class)
            ->setConstructorArgs([$this->createMock(OrderItemRepository::class)])
            ->onlyMethods(['resolve'])
            ->getMock();
        $resolver->method('resolve')->willReturn($items);

        return $resolver;
    }

    private function user(): User
    {
        return new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }
}
