<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Application\Exception\CartNotFoundException;
use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;

trait CartMutationTrait
{
    public function removeProduct(?string $token, Product $product, ?string $sellingType = null, ?int $rentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null): CartSession
    {
        $cart = $this->findCartByToken($token);
        if (null === $cart) {
            throw new CartNotFoundException();
        }

        $resolvedSellingType = $this->cartItems->normalizeSellingType($product, $sellingType ?? $product->getSellingType());
        $existing = $this->cartItems->resolveExistingItem($cart, $product, $resolvedSellingType, $rentalMonths, $rentalStartDate);
        if (null !== $existing) {
            $cart->removeItem($existing);
            $this->persistence->remove($existing);
            $this->saveCart($cart);
        }

        return $cart;
    }

    public function updateProductQuantity(?string $token, Product $product, string|int $sellingTypeOrQuantity, int|\DateTimeImmutable|null $quantityOrRentalMonths = null, mixed $currentSellingType = null, mixed $rentalMonths = null, mixed $currentRentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null, ?\DateTimeImmutable $currentRentalStartDate = null): CartSession
    {
        if (is_int($sellingTypeOrQuantity)) {
            $sellingType = $product->getSellingType();
            $quantity = $sellingTypeOrQuantity;
            $legacyCurrentSellingType = $currentSellingType;
            $legacyRentalMonths = $rentalMonths;
            $legacyCurrentRentalMonths = $currentRentalMonths;

            $currentSellingType = is_string($legacyCurrentSellingType) ? $legacyCurrentSellingType : $sellingType;
            $rentalMonths = is_int($quantityOrRentalMonths) ? $quantityOrRentalMonths : null;
            $currentRentalMonths = is_int($legacyCurrentSellingType) ? $legacyCurrentSellingType : (is_int($legacyCurrentRentalMonths) ? $legacyCurrentRentalMonths : null);
            $rentalStartDate = $quantityOrRentalMonths instanceof \DateTimeImmutable
                ? $quantityOrRentalMonths
                : ($legacyRentalMonths instanceof \DateTimeImmutable ? $legacyRentalMonths : $rentalStartDate);
            $currentRentalStartDate = $legacyCurrentRentalMonths instanceof \DateTimeImmutable
                ? $legacyCurrentRentalMonths
                : $currentRentalStartDate;
        } else {
            $sellingType = $sellingTypeOrQuantity;
            $quantity = is_int($quantityOrRentalMonths) ? $quantityOrRentalMonths : 0;
            $currentSellingType = is_string($currentSellingType) ? $currentSellingType : null;
            $rentalMonths = is_int($rentalMonths) ? $rentalMonths : null;
            $currentRentalMonths = is_int($currentRentalMonths) ? $currentRentalMonths : null;
        }

        if ($quantity < 0) {
            throw new \InvalidArgumentException('La quantite doit etre superieure ou egale a 0.');
        }

        $cart = $this->viewCart($token);
        $resolvedSellingType = $this->cartItems->normalizeSellingType($product, $sellingType);
        $lookupSellingType = $this->cartItems->normalizeSellingType($product, $currentSellingType ?? $sellingType);
        $lookupMonths = 'rental' === $lookupSellingType ? ($currentRentalMonths ?? $rentalMonths) : null;
        $lookupStartDate = 'rental' === $lookupSellingType ? ($currentRentalStartDate ?? $rentalStartDate) : null;
        $existing = $this->cartItems->resolveExistingItem($cart, $product, $lookupSellingType, $lookupMonths, $lookupStartDate);
        $resolvedRentalMonths = $this->resolveRentalMonthsForUpdate($product, $resolvedSellingType, $quantity, $rentalMonths, $existing);
        $resolvedRentalStartDate = $this->resolveRentalStartDateForUpdate($product, $resolvedSellingType, $quantity, $rentalStartDate, $existing);

        if ($quantity > 0) {
            $currentQuantity = $this->cartItems->getTotalQuantityForProduct($cart, $product, $resolvedSellingType, $existing);
            $this->assertStockAvailability($product, $currentQuantity + $quantity);
        }

        if (0 === $quantity) {
            if (null !== $existing) {
                $cart->removeItem($existing);
                $this->persistence->remove($existing);
            }
        } else {
            $this->upsertItemQuantity($cart, $product, $resolvedSellingType, $quantity, $resolvedRentalMonths, $resolvedRentalStartDate, $existing);
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

    private function resolveRentalMonthsForUpdate(Product $product, string $sellingType, int $quantity, ?int $rentalMonths, ?CartItem $existing): ?int
    {
        if ('rental' === $sellingType && $quantity > 0) {
            return $this->cartItems->determineRentalMonths($product, $sellingType, $rentalMonths, $existing);
        }

        return null;
    }

    private function resolveRentalStartDateForUpdate(Product $product, string $sellingType, int $quantity, ?\DateTimeImmutable $rentalStartDate, ?CartItem $existing): ?\DateTimeImmutable
    {
        if ('rental' === $sellingType && $quantity > 0) {
            return $this->cartItems->determineRentalStartDate($product, $sellingType, $rentalStartDate, $existing);
        }

        return null;
    }

    private function upsertItemQuantity(CartSession $cart, Product $product, string $sellingType, int $quantity, ?int $resolvedRentalMonths, ?\DateTimeImmutable $resolvedRentalStartDate, ?CartItem $existing): void
    {
        if (null === $existing) {
            if ('rental' === $sellingType && (null === $resolvedRentalMonths || null === $resolvedRentalStartDate)) {
                throw new \InvalidArgumentException('Les champs "rentalMonths" et "rentalStartDate" sont requis pour ce produit.');
            }

            $existing = new CartItem($cart, $product, $sellingType, $quantity, $resolvedRentalMonths, $resolvedRentalStartDate);
            $cart->addItem($existing);
            $this->persistence->persist($existing);

            return;
        }

        $skipQuantityUpdate = false;
        if (
            'rental' === $sellingType
            && null !== $resolvedRentalMonths
            && null !== $resolvedRentalStartDate
            && (
                $existing->getRentalMonths() !== $resolvedRentalMonths
                || $existing->getRentalStartDate()?->format('Y-m-d') !== $resolvedRentalStartDate->format('Y-m-d')
            )
        ) {
            $duplicate = $cart->getItemForProduct($product, $sellingType, $resolvedRentalMonths, $resolvedRentalStartDate);
            if (null !== $duplicate && $duplicate !== $existing) {
                $duplicate->increaseQuantity($quantity);
                $cart->removeItem($existing);
                $this->persistence->remove($existing);
                $skipQuantityUpdate = true;
            } else {
                $existing->setSellingType($sellingType);
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
