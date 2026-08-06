<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;

trait CartMutationTrait
{
    public function removeProduct(?string $token, Product $product, ?int $rentalMonths = null): CartSession
    {
        $cart = $this->findCartByToken($token);
        if (null === $cart) {
            throw new \InvalidArgumentException('Panier introuvable.');
        }

        $existing = $this->cartItems->resolveExistingItem($cart, $product, $rentalMonths);
        if (null !== $existing) {
            $cart->removeItem($existing);
            $this->persistence->remove($existing);
            $this->saveCart($cart);
        }

        return $cart;
    }

    public function updateProductQuantity(?string $token, Product $product, int $quantity, ?int $rentalMonths = null, ?int $currentRentalMonths = null): CartSession
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('La quantite doit etre superieure ou egale a 0.');
        }

        $cart = $this->viewCart($token);
        $lookupMonths = 'rental' === $product->getSellingType() ? ($currentRentalMonths ?? $rentalMonths) : null;
        $existing = $this->cartItems->resolveExistingItem($cart, $product, $lookupMonths);
        $resolvedRentalMonths = $this->resolveRentalMonthsForUpdate($product, $quantity, $rentalMonths, $existing);

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
            $this->upsertItemQuantity($cart, $product, $quantity, $resolvedRentalMonths, $existing);
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

    private function upsertItemQuantity(CartSession $cart, Product $product, int $quantity, ?int $resolvedRentalMonths, ?CartItem $existing): void
    {
        if (null === $existing) {
            if ('rental' === $product->getSellingType() && null === $resolvedRentalMonths) {
                throw new \InvalidArgumentException('Champ "rentalMonths" requis pour ce produit.');
            }

            $existing = new CartItem($cart, $product, $quantity, $resolvedRentalMonths);
            $cart->addItem($existing);
            $this->persistence->persist($existing);

            return;
        }

        $skipQuantityUpdate = false;
        if ('rental' === $product->getSellingType() && null !== $resolvedRentalMonths && $existing->getRentalMonths() !== $resolvedRentalMonths) {
            $duplicate = $cart->getItemForProduct($product, $resolvedRentalMonths);
            if (null !== $duplicate && $duplicate !== $existing) {
                $duplicate->increaseQuantity($quantity);
                $cart->removeItem($existing);
                $this->persistence->remove($existing);
                $skipQuantityUpdate = true;
            } else {
                $existing->setRentalMonths($resolvedRentalMonths);
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
        $this->persistence->commit();
    }
}
