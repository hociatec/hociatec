<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\User\Entity\User;
use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Repository\VoucherLookupInterface;
use App\Module\Voucher\Service\VoucherEngine;
use PHPUnit\Framework\TestCase;

final class VoucherEngineTest extends TestCase
{
    public function testCalculateForSubtotalHandlesNoCodeInvalidAndIneligibleCodes(): void
    {
        $repository = $this->createMock(VoucherLookupInterface::class);
        $repository->method('findOneByCode')->willReturnCallback(function (string $code): ?Voucher {
            return match (mb_strtoupper(trim($code))) {
                'DISABLED' => $this->voucher('Disabled', 'disabled', Voucher::TYPE_FIXED_CENTS, 1000)->setIsActive(false),
                'FUTURE' => $this->voucher('Future', 'future', Voucher::TYPE_FIXED_CENTS, 1000)
                    ->setEndsAt(new \DateTimeImmutable('+3 day'))
                    ->setStartsAt(new \DateTimeImmutable('+2 day')),
                'PAST' => $this->voucher('Past', 'past', Voucher::TYPE_FIXED_CENTS, 1000)
                    ->setStartsAt(new \DateTimeImmutable('-3 day'))
                    ->setEndsAt(new \DateTimeImmutable('-2 day')),
                default => null,
            };
        });

        $engine = new VoucherEngine($repository);

        self::assertSame('none', $engine->calculateForSubtotal(10000, null)['voucherCodeStatus']);
        self::assertSame('invalid', $engine->calculateForSubtotal(10000, null, 'unknown')['voucherCodeStatus']);
        self::assertSame('ineligible', $engine->calculateForSubtotal(10000, null, 'disabled')['voucherCodeStatus']);
        self::assertSame('ineligible', $engine->calculateForSubtotal(10000, null, 'future')['voucherCodeStatus']);
        self::assertSame('ineligible', $engine->calculateForSubtotal(10000, null, 'past')['voucherCodeStatus']);
    }

    public function testCalculateForSubtotalAppliesBestVoucherDataForEligibleUser(): void
    {
        $user = new User('Ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setEntityId($user, 42);

        $repository = $this->createMock(VoucherLookupInterface::class);
        $repository->method('findOneByCode')->willReturnCallback(function (string $code): ?Voucher {
            return match (mb_strtoupper(trim($code))) {
                'PERCENT20' => $this->voucher('Percent', 'PERCENT20', Voucher::TYPE_PERCENT, 20),
                'USERONLY' => $this->voucher('User only', 'USERONLY', Voucher::TYPE_FIXED_CENTS, 2500)->setRecipientUserId(42),
                'MAILONLY' => $this->voucher('Mail only', 'MAILONLY', Voucher::TYPE_FIXED_CENTS, 3000)->setRecipientEmail('ada@example.com'),
                'OTHERUSER' => $this->voucher('Other', 'OTHERUSER', Voucher::TYPE_FIXED_CENTS, 5000)->setRecipientUserId(99),
                default => null,
            };
        });

        $engine = new VoucherEngine($repository);

        $percent = $engine->calculateForSubtotal(20000, $user, ' percent20 ');
        self::assertSame('applied', $percent['voucherCodeStatus']);
        self::assertSame('PERCENT20', $percent['enteredVoucherCode']);
        self::assertSame(4000, $percent['discountAmountCents']);
        self::assertSame(16000, $percent['totalPriceCents']);

        $userOnly = $engine->calculateForSubtotal(20000, $user, 'USERONLY');
        self::assertSame('applied', $userOnly['voucherCodeStatus']);
        self::assertSame(2500, $userOnly['discountAmountCents']);

        $mailOnly = $engine->calculateForSubtotal(20000, $user, 'MAILONLY');
        self::assertSame('applied', $mailOnly['voucherCodeStatus']);
        self::assertSame(3000, $mailOnly['discountAmountCents']);

        $otherUser = $engine->calculateForSubtotal(20000, $user, 'OTHERUSER');
        self::assertSame('ineligible', $otherUser['voucherCodeStatus']);
    }

    public function testCalculateCartSummaryUsesCartVoucherCodeAndRentalMonths(): void
    {
        $category = new Category('Phones', 'phones');
        $saleProduct = new Product('Phone', 'phone', 'PH-1', 'Sale', 10000, 5, $category);
        $rentalProduct = new Product('Rental', 'rental', 'RE-1', 'Rental', 5000, 5, $category);
        $rentalProduct->setSellingType('rental');

        $cart = new CartSession('cart-token');
        $cart->addItem(new CartItem($cart, $saleProduct, 1));
        $cart->addItem(new CartItem($cart, $rentalProduct, 2, 3));
        $cart->setVoucherCode('voucher10');

        $repository = $this->createMock(VoucherLookupInterface::class);
        $repository->method('findOneByCode')->with('VOUCHER10')->willReturn(
            $this->voucher('Voucher 10', 'VOUCHER10', Voucher::TYPE_FIXED_CENTS, 10000)
        );

        $summary = (new VoucherEngine($repository))->calculateCartSummary($cart, null);

        self::assertSame(40000, $summary['subtotalPriceCents']);
        self::assertSame(10000, $summary['discountAmountCents']);
        self::assertSame(30000, $summary['totalPriceCents']);
        self::assertSame('applied', $summary['voucherCodeStatus']);
        self::assertSame('VOUCHER10', $summary['enteredVoucherCode']);
    }

    public function testCalculateCartSummaryPrefersExplicitVoucherCodeAndHandlesRecipientAndZeroSubtotalBranches(): void
    {
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Sale', 10000, 5, $category);
        $cart = new CartSession('cart-token');
        $cart->addItem(new CartItem($cart, $product, 1));
        $cart->setVoucherCode('ignored');

        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        $repository = $this->createMock(VoucherLookupInterface::class);
        $repository->method('findOneByCode')->willReturnCallback(function (string $code): ?Voucher {
            return match (mb_strtoupper(trim($code))) {
                'DIRECT' => $this->voucher('Direct', 'DIRECT', Voucher::TYPE_FIXED_CENTS, 1500),
                'PRIVATE' => $this->voucher('Private', 'PRIVATE', Voucher::TYPE_FIXED_CENTS, 1500)->setRecipientEmail('private@example.com'),
                'ZERO' => $this->voucher('Zero', 'ZERO', Voucher::TYPE_FIXED_CENTS, 1500),
                'ZERODISCOUNT' => $this->voucher('Zero discount', 'ZERODISCOUNT', Voucher::TYPE_FIXED_CENTS, 0),
                default => null,
            };
        });

        $engine = new VoucherEngine($repository);

        $explicit = $engine->calculateCartSummary($cart, null, 'direct');
        self::assertSame('DIRECT', $explicit['enteredVoucherCode']);
        self::assertSame('applied', $explicit['voucherCodeStatus']);
        self::assertSame(1500, $explicit['discountAmountCents']);

        $recipientDenied = $engine->calculateForSubtotal(10000, null, 'PRIVATE');
        self::assertSame('ineligible', $recipientDenied['voucherCodeStatus']);

        $zeroSubtotal = $engine->calculateForSubtotal(10000, null, 'ZERODISCOUNT');
        self::assertSame('ineligible', $zeroSubtotal['voucherCodeStatus']);
        self::assertSame(0, $zeroSubtotal['discountAmountCents']);

        $zeroSubtotalDiscount = $engine->calculateForSubtotal(0, null, 'ZERO');
        self::assertSame('ineligible', $zeroSubtotalDiscount['voucherCodeStatus']);
        self::assertSame(0, $zeroSubtotalDiscount['discountAmountCents']);
    }

    public function testComputeDiscountAmountReturnsZeroForNonPositiveSubtotal(): void
    {
        $engine = new VoucherEngine($this->createMock(VoucherLookupInterface::class));
        $method = new \ReflectionMethod($engine, 'computeDiscountAmount');
        $method->setAccessible(true);

        $zeroSubtotal = $method->invoke($engine, $this->voucher('Zero', 'ZERO', Voucher::TYPE_FIXED_CENTS, 1500), 0);

        self::assertSame(0, $zeroSubtotal);
    }

    private function voucher(string $name, string $code, string $type, int $value): Voucher
    {
        $voucher = new Voucher($name, $code, $type, $value);
        $voucher->setStartsAt(new \DateTimeImmutable('-1 day'));
        $voucher->setEndsAt(new \DateTimeImmutable('+1 day'));

        return $voucher;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
