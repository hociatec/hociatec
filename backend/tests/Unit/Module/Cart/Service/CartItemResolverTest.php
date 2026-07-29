<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Cart\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Cart\Service\CartItemResolver;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use PHPUnit\Framework\TestCase;

final class CartItemResolverTest extends TestCase
{
    public function testDetermineRentalMonthsReturnsNullForSaleProducts(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('sale');

        self::assertNull($resolver->determineRentalMonths($product, 12));
    }

    public function testDetermineRentalMonthsUsesExistingItemWhenMissingFromRequest(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('rental');
        $cart = new CartSession('token');
        $item = new CartItem($cart, $product, 1, 6);

        self::assertSame(6, $resolver->determineRentalMonths($product, null, $item));
    }

    public function testDetermineRentalMonthsRejectsMissingMonthsForRentalProduct(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('rental');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Champ "rentalMonths" requis pour ce produit.');

        $resolver->determineRentalMonths($product, null);
    }

    public function testResolveExistingItemRejectsAmbiguousRentalSelection(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('rental');
        $cart = new CartSession('token');
        $cart->addItem(new CartItem($cart, $product, 1, 3));
        $cart->addItem(new CartItem($cart, $product, 1, 6));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Plusieurs durées de location existent pour ce produit. Précisez "currentRentalMonths".');

        $resolver->resolveExistingItem($cart, $product);
    }

    public function testDetermineRentalMonthsRejectsNonPositiveValue(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('rental');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée de location doit être supérieure ou égale à 1 mois.');

        $resolver->determineRentalMonths($product, 0);
    }

    public function testResolveExistingItemSupportsSaleAndExplicitRentalDuration(): void
    {
        $resolver = new CartItemResolver();
        $saleProduct = $this->product('sale');
        $rentalProduct = $this->product('rental');
        $cart = new CartSession('token');

        $saleItem = new CartItem($cart, $saleProduct, 1);
        $threeMonths = new CartItem($cart, $rentalProduct, 1, 3);
        $sixMonths = new CartItem($cart, $rentalProduct, 1, 6);

        $cart->addItem($saleItem);
        $cart->addItem($threeMonths);
        $cart->addItem($sixMonths);

        self::assertSame($saleItem, $resolver->resolveExistingItem($cart, $saleProduct));
        self::assertSame($sixMonths, $resolver->resolveExistingItem($cart, $rentalProduct, 6));
    }

    public function testResolverReturnsNullWhenNoRentalItemMatchesAndReturnsProvidedMonths(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('rental');
        $cart = new CartSession('token');
        $cart->addItem(new CartItem($cart, $product, 1, 3));

        self::assertSame(12, $resolver->determineRentalMonths($product, 12));
        self::assertNull($resolver->resolveExistingItem($cart, $product, 12));
    }

    public function testResolverReturnsSingleRentalItemWithoutExplicitDuration(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('rental');
        $cart = new CartSession('token');
        $item = new CartItem($cart, $product, 1, 3);
        $cart->addItem($item);

        self::assertSame($item, $resolver->resolveExistingItem($cart, $product));
    }

    public function testGetTotalQuantityForProductCanExcludeOneItem(): void
    {
        $resolver = new CartItemResolver();
        $product = $this->product('sale');
        $cart = new CartSession('token');
        $first = new CartItem($cart, $product, 2);
        $second = new CartItem($cart, $product, 3);
        $cart->addItem($first);
        $cart->addItem($second);

        self::assertSame(5, $resolver->getTotalQuantityForProduct($cart, $product));
        self::assertSame(3, $resolver->getTotalQuantityForProduct($cart, $product, $first));
    }

    private function product(string $sellingType): Product
    {
        $category = new Category('Telephone', 'telephone');
        $product = new Product('Produit test', 'produit-test', 'SKU-TEST', 'Description', 1990, 10, $category);
        $product->setSellingType($sellingType);

        return $product;
    }
}
