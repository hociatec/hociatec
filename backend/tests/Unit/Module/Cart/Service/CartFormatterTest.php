<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Cart\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Cart\Service\CartFormatter;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Promotion\Repository\PromotionRepository;
use App\Module\Promotion\Service\PromotionEngine;
use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Service\VoucherEngine;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class CartFormatterTest extends TestCase
{
    public function testFormatCartFallsBackToBaseTotalsWhenPromotionAndVoucherEnginesFail(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->willReturn(null);

        $formatter = new CartFormatter(
            new PromotionEngine(new PromotionRepository($registry)),
            new VoucherEngine(new VoucherRepository($registry)),
        );

        $category = new Category('Telephone', 'telephone');
        $product = (new Product('Produit test', 'produit-test', 'SKU-TEST', 'Description', 1990, 5, $category))
            ->setIsPublished(true);

        $cart = new CartSession('cart-token');
        $cart->setVoucherCode('BROKEN');
        $cart->addItem(new CartItem($cart, $product, 2));

        $payload = $formatter->formatCart($cart);

        self::assertSame('cart-token', $payload['token']);
        self::assertSame(2, $payload['totalQuantity']);
        self::assertSame(3980, $payload['subtotalPriceCents']);
        self::assertSame(0, $payload['discountAmountCents']);
        self::assertSame(3980, $payload['totalPriceCents']);
        self::assertNull($payload['appliedPromotion']);
        self::assertSame([], $payload['eligiblePromotions']);
        self::assertNull($payload['appliedVoucher']);
        self::assertNull($payload['enteredVoucherCode']);
        self::assertSame('none', $payload['voucherCodeStatus']);
    }

    public function testFormatCartPrefersAppliedVoucherSummaryAndHandlesRentalLines(): void
    {
        $category = new Category('Telephone', 'telephone');
        $product = (new Product('Produit location', 'produit-location', 'SKU-LOC', 'Description', 5000, 5, $category))
            ->setSellingType('rental')
            ->setIsPublished(true);

        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository->method('findActiveForDate')->willReturn([
            (new \App\Module\Promotion\Entity\Promotion('Promo', 'promo', \App\Module\Promotion\Entity\Promotion::TYPE_FIXED_CENTS, 5000, 'all_users'))
                ->setStartsAt(new \DateTimeImmutable('-1 day'))
                ->setEndsAt(new \DateTimeImmutable('+1 day')),
        ]);

        $voucherRepository = $this->createMock(\App\Module\Voucher\Repository\VoucherLookupInterface::class);
        $voucherRepository->method('findOneByCode')->with('VOUCHER8')->willReturn(
            (new \App\Module\Voucher\Entity\Voucher('Voucher 8', 'VOUCHER8', \App\Module\Voucher\Entity\Voucher::TYPE_FIXED_CENTS, 8000))
                ->setStartsAt(new \DateTimeImmutable('-1 day'))
                ->setEndsAt(new \DateTimeImmutable('+1 day'))
        );

        $formatter = new CartFormatter(
            new PromotionEngine($promotionRepository),
            new VoucherEngine($voucherRepository),
        );

        $cart = new CartSession('cart-token');
        $cart->setVoucherCode('voucher8');
        $cart->addItem(new CartItem($cart, $product, 2, 3));

        $payload = $formatter->formatCart($cart);

        self::assertSame(30000, $payload['subtotalPriceCents']);
        self::assertSame(8000, $payload['discountAmountCents']);
        self::assertSame(22000, $payload['totalPriceCents']);
        self::assertSame('promo', $payload['appliedPromotion']['slug']);
        self::assertSame('promo', $payload['eligiblePromotions'][0]['slug']);
        self::assertSame('VOUCHER8', $payload['appliedVoucher']['code']);
        self::assertSame('VOUCHER8', $payload['enteredVoucherCode']);
        self::assertSame(30000, $payload['items'][0]['linePriceCents']);
    }

}
