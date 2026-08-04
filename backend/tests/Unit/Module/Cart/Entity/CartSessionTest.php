<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Cart\Entity;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use PHPUnit\Framework\TestCase;

final class CartSessionTest extends TestCase
{
    public function testItReturnsTheMatchingSaleProductItem(): void
    {
        $cart = new CartSession('token');
        $product = $this->product('sale');
        $item = new CartItem($cart, $product, 2);
        $cart->addItem($item);

        self::assertTrue($cart->hasProduct($product));
        self::assertSame($item, $cart->getItemForProduct($product));
        self::assertSame([$item], $cart->getItemsForProduct($product));
    }

    public function testItCanSelectRentalItemsByDurationOrFallbackToFirstMatch(): void
    {
        $cart = new CartSession('token');
        $product = $this->product('rental');
        $threeMonths = new CartItem($cart, $product, 1, 3);
        $sixMonths = new CartItem($cart, $product, 1, 6);
        $cart->addItem($threeMonths);
        $cart->addItem($sixMonths);

        self::assertSame($sixMonths, $cart->getItemForProduct($product, 6));
        self::assertSame($threeMonths, $cart->getItemForProduct($product));
    }

    public function testVoucherCodeIsNormalizedAndCanBeCleared(): void
    {
        $cart = new CartSession('token');

        $cart->setVoucherCode('  summer2026 ');
        self::assertSame('SUMMER2026', $cart->getVoucherCode());

        $cart->setVoucherCode(null);
        self::assertNull($cart->getVoucherCode());
    }

    public function testConvertedStateIsUpdatedWhenOrderIsMarkedConverted(): void
    {
        $cart = new CartSession('token');

        self::assertFalse($cart->isConverted());
        self::assertNull($cart->getConvertedOrderId());

        $cart->markConverted(123);

        self::assertTrue($cart->isConverted());
        self::assertSame(123, $cart->getConvertedOrderId());
        self::assertNotNull($cart->getConvertedAt());
    }

    public function testItSupportsLifecycleAndUserMutations(): void
    {
        $cart = new CartSession('token');
        $product = $this->product('sale');
        $item = new CartItem($cart, $product, 1);
        $user = new \App\Module\User\Domain\Entity\User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');

        self::assertNull($cart->getId());
        self::assertFalse($cart->hasProduct($product));
        self::assertSame('token', $cart->getToken());
        self::assertInstanceOf(\DateTimeImmutable::class, $cart->getCreatedAt());
        self::assertNull($cart->getUser());

        $cart->addItem($item);
        $cart->removeItem($item);
        self::assertCount(0, $cart->getItems());

        $cart->setUser($user);
        self::assertSame($user, $cart->getUser());

        $cart->setVoucherCode('   ');
        self::assertNull($cart->getVoucherCode());

        $beforePersist = $cart->getUpdatedAt();
        usleep(1000);
        $cart->onPrePersist();
        self::assertGreaterThanOrEqual($beforePersist, $cart->getUpdatedAt());

        $beforeUpdate = $cart->getUpdatedAt();
        usleep(1000);
        $cart->onPreUpdate();
        self::assertGreaterThan($beforeUpdate, $cart->getUpdatedAt());
    }

    public function testRemovingUnknownItemIsANoOp(): void
    {
        $cart = new CartSession('token');
        $item = new CartItem($cart, $this->product('sale'), 1);

        $cart->removeItem($item);

        self::assertCount(0, $cart->getItems());
    }

    private function product(string $sellingType): Product
    {
        $category = new Category('Telephone', 'telephone');
        $product = new Product('Produit test', 'produit-test', 'SKU-TEST', 'Description', 1990, 10, $category);
        $product->setSellingType($sellingType);

        return $product;
    }
}
