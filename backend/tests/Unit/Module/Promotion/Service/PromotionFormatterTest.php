<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Promotion\Service;

use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Module\Promotion\Domain\Entity\Promotion;
use PHPUnit\Framework\TestCase;

final class PromotionFormatterTest extends TestCase
{
    public function testItFormatsPromotion(): void
    {
        $promotion = new Promotion('Promo', 'promo', Promotion::TYPE_PERCENT, 10, 'all_users', ['minimumCartTotalCents' => 1000]);
        $this->setEntityId($promotion, 99);
        $promotion
            ->setDescription('Desc')
            ->setStartsAt(new \DateTimeImmutable('-1 day'))
            ->setEndsAt(new \DateTimeImmutable('+1 day'));

        $formatted = (new PromotionFormatter())->formatPromotion($promotion);

        self::assertSame(99, $formatted['id']);
        self::assertSame('Promo', $formatted['name']);
        self::assertSame('promo', $formatted['slug']);
        self::assertSame('Desc', $formatted['description']);
        self::assertSame(Promotion::TYPE_PERCENT, $formatted['discountType']);
        self::assertSame(10, $formatted['discountValue']);
        self::assertSame('all_users', $formatted['audienceKey']);
        self::assertSame(['minimumCartTotalCents' => 1000], $formatted['criteria']);
        self::assertTrue($formatted['isActive']);
        self::assertIsString($formatted['createdAt']);
        self::assertIsString($formatted['updatedAt']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
