<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Cart\Entity;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use PHPUnit\Framework\TestCase;

final class CartItemTest extends TestCase
{
    public function testItIncreasesQuantityAndSupportsReplacingTheProduct(): void
    {
        $cart = new CartSession('token');
        $product = $this->product();
        $replacement = $this->product('SKU-NEW');
        $item = new CartItem($cart, $product, 2);

        self::assertNull($item->getId());
        self::assertSame($cart, $item->getCart());
        $item->increaseQuantity(3);
        $item->replaceProduct($replacement);

        self::assertSame(5, $item->getQuantity());
        self::assertSame($replacement, $item->getProduct());
    }

    public function testItRejectsInvalidQuantities(): void
    {
        $cart = new CartSession('token');
        $product = $this->product();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La quantite doit etre superieure ou egale a 1.');

        new CartItem($cart, $product, 0);
    }

    public function testItRejectsInvalidQuantityIncrease(): void
    {
        $cart = new CartSession('token');
        $product = $this->product();
        $item = new CartItem($cart, $product, 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'augmentation doit etre superieure ou egale a 1.");

        $item->increaseQuantity(0);
    }

    public function testItNormalizesRentalMonths(): void
    {
        $cart = new CartSession('token');
        $product = $this->product();
        $item = new CartItem($cart, $product, 1);

        self::assertNull($item->getRentalMonths());

        $item->setRentalMonths(6);
        self::assertSame(6, $item->getRentalMonths());

        $item->setRentalMonths(null);
        self::assertNull($item->getRentalMonths());
    }

    public function testItRejectsInvalidRentalMonths(): void
    {
        $cart = new CartSession('token');
        $product = $this->product();
        $item = new CartItem($cart, $product, 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée de location doit être supérieure ou égale à 1 mois.');

        $item->setRentalMonths(0);
    }

    private function product(string $sku = 'SKU-TEST'): Product
    {
        $category = new Category('Telephone', 'telephone');

        return new Product('Produit test', strtolower($sku), $sku, 'Description', 1990, 10, $category);
    }
}
