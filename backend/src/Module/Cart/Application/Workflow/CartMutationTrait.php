<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Application\Exception\CartNotFoundException;
use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;

trait CartMutationTrait
{
    public function removeProduct(?string $token, Product $product, ?int $rentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null): CartSession
    {
        $cart = $this->findCartByToken($token);
        if (null === $cart) {
            throw new CartNotFoundException();
        }

        $existing = $this->cartItems->resolveExistingItem($cart, $product, $rentalMonths, $rentalStartDate);
        if (null !== $existing) {
            $cart->removeItem($existing);
            $this->persistence->remove($existing);
            $this->saveCart($cart);
        }

        return $cart;
    }

    public function updateProductQuantity(?string $token, Product $product, int $quantity, ?int $rentalMonths = null, ?int $currentRentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null, ?\DateTimeImmutable $currentRentalStartDate = null): CartSession
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('La quantite doit etre superieure ou egale a 0.');
        }

        $cart = $this->viewCart($token);
        $lookupMonths = 'rental' === $product->getSellingType() ? ($currentRentalMonths ?? $rentalMonths) : null;
        $lookupStartDate = 'rental' === $product->getSellingType() ? ($currentRentalStartDate ?? $rentalStartDate) : null;
        $existing = $this->cartItems->resolveExistingItem($cart, $product, $lookupMonths, $lookupStartDate);
        $resolvedRentalMonths = $this->resolveRentalMonthsForUpdate($product, $quantity, $rentalMonths, $existing);
        $resolvedRentalStartDate = $this->resolveRentalStartDateForUpdate($product, $quantity, $rentalStartDate, $existing);

        if ($quantity > 0) {
            $currentQuantity = $this->cartItems->getTotalQuantityForProduct($cart, $product, $existing);
            $this->assertStockAvailability($product, $currentQuantity + $quantity);
        }

        if (0 === $quantity) {
            if (null !== $existing) {
                $cart->removeItem($existing);
                $this->persistence->remove($existing);
            }
        } else {
            $this->upsertItemQuantity($cart, $product, $quantity, $resolvedRentalMonths, $resolvedRentalStartDate, $existing);
        }

        $this->saveCart($cart);

        return $cart;
    }

    public function clearCart(?string $token): CartSession
    {
        $cart = $this->viewCart($token);

        foreach ($cart->getItems()->toArray() as $item) {
            $cart->removeItem($item);
            $this->persistence->remove($item);
        }

        $this->saveCart($cart);

        return $cart;
    }

    private function resolveRentalMonthsForUpdate(Product $product, int $quantity, ?int $rentalMonths, ?CartItem $existing): ?int
    {
        if ('rental' === $product->getSellingType() && $quantity > 0) {
            return $this->cartItems->determineRentalMonths($product, $rentalMonths, $existing);
        }

        return null;
    }

    private function resolveRentalStartDateForUpdate(Product $product, int $quantity, ?\DateTimeImmutable $rentalStartDate, ?CartItem $existing): ?\DateTimeImmutable
    {
        if ('rental' === $product->getSellingType() && $quantity > 0) {
            return $this->cartItems->determineRentalStartDate($product, $rentalStartDate, $existing);
        }

        return null;
    }

    private function upsertItemQuantity(CartSession $cart, Product $product, int $quantity, ?int $resolvedRentalMonths, ?\DateTimeImmutable $resolvedRentalStartDate, ?CartItem $existing): void
    {
        if (null === $existing) {
            if ('rental' === $product->getSellingType() && (null === $resolvedRentalMonths || null === $resolvedRentalStartDate)) {
                throw new \InvalidArgumentException('Les champs "rentalMonths" et "rentalStartDate" sont requis pour ce produit.');
            }

            $existing = new CartItem($cart, $product, $quantity, $resolvedRentalMonths, $resolvedRentalStartDate);
            $cart->addItem($existing);
            $this->persistence->persist($existing);

            return;
        }

        $skipQuantityUpdate = false;
        if (
            'rental' === $product->getSellingType()
            && null !== $resolvedRentalMonths
            && null !== $resolvedRentalStartDate
            && (
                $existing->getRentalMonths() !== $resolvedRentalMonths
                || $existing->getRentalStartDate()?->format('Y-m-d') !== $resolvedRentalStartDate->format('Y-m-d')
            )
        ) {
            $duplicate = $cart->getItemForProduct($product, $resolvedRentalMonths, $resolvedRentalStartDate);
            if (null !== $duplicate && $duplicate !== $existing) {
                $duplicate->increaseQuantity($quantity);
                $cart->removeItem($existing);
                $this->persistence->remove($existing);
                $skipQuantityUpdate = true;
            } else {
                $existing->setRentalMonths($resolvedRentalMonths);
                $existing->setRentalStartDate($resolvedRentalStartDate);
            }
        }

        if (!$skipQuantityUpdate) {
            $existing->setQuantity($quantity);
        }
    }

    private function saveCart(CartSession $cart): void
    {
        $cart->touch();
        $this->persistence->persist($cart);
        $this->persistence->flush();
    }
}
