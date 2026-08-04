<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Promotion\Entity;

use App\Module\Promotion\Domain\Entity\Promotion;
use PHPUnit\Framework\TestCase;

final class PromotionTest extends TestCase
{
    public function testPromotionMutatorsAndLifecycle(): void
    {
        $promotion = new Promotion('Summer', 'summer', Promotion::TYPE_PERCENT, 15, 'all_users', ['minimumCartTotalCents' => 1000]);
        $originalUpdatedAt = $promotion->getUpdatedAt();

        self::assertSame('Summer', $promotion->getName());
        self::assertSame('summer', $promotion->getSlug());
        self::assertSame(Promotion::TYPE_PERCENT, $promotion->getDiscountType());
        self::assertSame(15, $promotion->getDiscountValue());
        self::assertSame('all_users', $promotion->getAudienceKey());
        self::assertSame(['minimumCartTotalCents' => 1000], $promotion->getCriteria());
        self::assertTrue($promotion->isActive());

        $promotion
            ->setName('Winter')
            ->setSlug('winter')
            ->setDescription('Promo')
            ->setDiscountType(Promotion::TYPE_FIXED_CENTS)
            ->setDiscountValue(-100)
            ->setAudienceKey('loyal_customers')
            ->setCriteria(['minimumOrders' => 3])
            ->setIsActive(false)
            ->setStartsAt(new \DateTimeImmutable('+1 day'))
            ->setEndsAt(new \DateTimeImmutable('+2 day'));

        self::assertSame('Winter', $promotion->getName());
        self::assertSame('winter', $promotion->getSlug());
        self::assertSame('Promo', $promotion->getDescription());
        self::assertSame(Promotion::TYPE_FIXED_CENTS, $promotion->getDiscountType());
        self::assertSame(0, $promotion->getDiscountValue());
        self::assertSame('loyal_customers', $promotion->getAudienceKey());
        self::assertSame(['minimumOrders' => 3], $promotion->getCriteria());
        self::assertFalse($promotion->isActive());
        self::assertInstanceOf(\DateTimeImmutable::class, $promotion->getStartsAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $promotion->getEndsAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $promotion->getCreatedAt());

        usleep(1000);
        $promotion->touch();
        self::assertGreaterThanOrEqual($originalUpdatedAt, $promotion->getUpdatedAt());
    }
}
