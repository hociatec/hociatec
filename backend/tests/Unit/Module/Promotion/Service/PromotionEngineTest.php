<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Promotion\Service;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Promotion\Application\Calculator\CartSubtotalCalculator;
use App\Module\Promotion\Application\Calculator\PromotionDiscountCalculator;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\Promotion\Application\Policy\PromotionEligibilityPolicy;
use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Module\Promotion\Application\Provider\PromotionAudienceProvider;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class PromotionEngineTest extends TestCase
{
    public function testAudienceDefinitionsExposeExpectedDefaults(): void
    {
        $engine = $this->engine($this->createMock(PromotionRepository::class));

        $definitions = $engine->getAudienceDefinitions();

        self::assertArrayHasKey('all_users', $definitions);
        self::assertArrayHasKey('new_users', $definitions);
        self::assertArrayHasKey('loyal_customers', $definitions);
        self::assertSame(0, $definitions['all_users']['defaults']['minimumCartTotalCents']);
        self::assertSame(30, $definitions['new_users']['defaults']['registeredDays']);
        self::assertSame(3, $definitions['loyal_customers']['defaults']['minimumOrders']);
    }

    public function testCalculateForSubtotalSelectsTheBestEligiblePromotion(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->onPrePersist();

        $promotions = [
            $this->promotion('Tous 10%', 'all-10', Promotion::TYPE_PERCENT, 10, 'all_users'),
            $this->promotion('Nouveau 15%', 'new-15', Promotion::TYPE_PERCENT, 15, 'new_users', ['registeredDays' => 30]),
            $this->promotion('Inactif 20%', 'inactive-20', Promotion::TYPE_PERCENT, 20, 'inactive_customers', ['inactiveDays' => 90]),
            $this->promotion('Panier min', 'min-100', Promotion::TYPE_FIXED_CENTS, 5000, 'all_users', ['minimumCartTotalCents' => 100000]),
            $this->promotion('Inactive', 'disabled', Promotion::TYPE_FIXED_CENTS, 9999, 'all_users')->setIsActive(false),
            $this->promotion('Futur', 'future', Promotion::TYPE_PERCENT, 50, 'all_users')->setStartsAt(new \DateTimeImmutable('+1 day')),
            $this->promotion('Expire', 'past', Promotion::TYPE_PERCENT, 50, 'all_users')->setEndsAt(new \DateTimeImmutable('-1 day')),
        ];

        $repository = $this->createMock(PromotionRepository::class);
        $repository->method('findActiveForDate')->willReturn($promotions);
        $repository->method('findUserOrderStats')->with($user)->willReturn([
            'ordersCount' => 2,
            'lastOrderAt' => new \DateTimeImmutable('-120 days'),
        ]);

        $summary = $this->engine($repository)->calculateForSubtotal(50000, $user);

        self::assertSame(50000, $summary['subtotalPriceCents']);
        self::assertSame(10000, $summary['discountAmountCents']);
        self::assertSame(40000, $summary['totalPriceCents']);
        self::assertSame('inactive-20', $summary['appliedPromotion']['slug']);
        self::assertCount(3, $summary['eligiblePromotions']);
    }

    public function testCalculateForSubtotalHandlesGuestAndZeroSubtotal(): void
    {
        $repository = $this->createMock(PromotionRepository::class);
        $repository->method('findActiveForDate')->willReturn([
            $this->promotion('Fixe', 'fixed', Promotion::TYPE_FIXED_CENTS, 2000, 'all_users'),
        ]);

        $summary = $this->engine($repository)->calculateForSubtotal(0, null);

        self::assertSame(0, $summary['subtotalPriceCents']);
        self::assertSame(0, $summary['discountAmountCents']);
        self::assertSame(0, $summary['totalPriceCents']);
        self::assertNull($summary['appliedPromotion']);
        self::assertSame([], $summary['eligiblePromotions']);
    }

    public function testCalculateCartSummaryIncludesRentalMonthsAndUserStatsBranches(): void
    {
        $category = new Category('Phones', 'phones');
        $saleProduct = new Product('Phone', 'phone', 'PH-1', 'Sale', 10000, 5, $category);
        $rentalProduct = new Product('Rental', 'rental', 'RE-1', 'Rental', 5000, 5, $category);
        $rentalProduct->setSellingType('rental');

        $cart = new CartSession('cart-token');
        $cart->addItem(new CartItem($cart, $saleProduct, 2));
        $cart->addItem(new CartItem($cart, $rentalProduct, 1, 3));

        $user = new User('grace@example.com', 'Grace', 'Hopper', new \DateTimeImmutable('1985-01-01'), '0102030405', 'female');
        $user->onPrePersist();

        $repository = $this->createMock(PromotionRepository::class);
        $repository->method('findActiveForDate')->willReturn([
            $this->promotion('Premiere', 'first-order', Promotion::TYPE_PERCENT, 10, 'first_order_users'),
            $this->promotion('Retour', 'returning', Promotion::TYPE_PERCENT, 5, 'returning_customers'),
            $this->promotion('Fidele', 'loyal', Promotion::TYPE_PERCENT, 30, 'loyal_customers', ['minimumOrders' => 4]),
        ]);
        $repository->method('findUserOrderStats')->with($user)->willReturn([
            'ordersCount' => 0,
            'lastOrderAt' => null,
        ]);

        $summary = $this->engine($repository)->calculateCartSummary($cart, $user);

        self::assertSame(35000, $summary['subtotalPriceCents']);
        self::assertSame(3500, $summary['discountAmountCents']);
        self::assertSame(31500, $summary['totalPriceCents']);
        self::assertSame('first-order', $summary['appliedPromotion']['slug']);
        self::assertCount(1, $summary['eligiblePromotions']);
    }

    private function promotion(
        string $name,
        string $slug,
        string $discountType,
        int $discountValue,
        string $audienceKey,
        array $criteria = [],
    ): Promotion {
        $promotion = new Promotion($name, $slug, $discountType, $discountValue, $audienceKey, $criteria);
        $promotion->setStartsAt(new \DateTimeImmutable('-1 day'));
        $promotion->setEndsAt(new \DateTimeImmutable('+1 day'));

        return $promotion;
    }

    private function engine(PromotionRepository $repository): PromotionEngine
    {
        return new PromotionEngine(
            $repository,
            new PromotionFormatter(),
            new PromotionAudienceProvider(),
            new CartSubtotalCalculator(),
            new PromotionDiscountCalculator(),
            new PromotionEligibilityPolicy(),
            new MockClock('2026-08-11T10:00:00+00:00'),
        );
    }
}
