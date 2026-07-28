<?php

declare(strict_types=1);

namespace App\Module\Cart\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Product;
use App\Shared\Persistence\DoctrinePersistence;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class CartService
{
    public function __construct(
        private readonly CartSessionProvider $cartSessions,
        private readonly CartItemResolver $cartItems,
        private readonly DoctrinePersistence $persistence,
    ) {
    }

    public function viewCart(?string $token): CartSession
    {
        return $this->cartSessions->view($token);
    }

    public function addProduct(?string $token, Product $product, int $quantity = 1, ?int $rentalMonths = null, bool $retryOnDuplicate = true): CartSession
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantite doit etre superieure ou egale a 1.');
        }

        $cart = $this->viewCart($token);
        $resolvedRentalMonths = $this->cartItems->determineRentalMonths($product, $rentalMonths);
        $existing = $this->cartItems->resolveExistingItem($cart, $product, $resolvedRentalMonths);

        $currentQuantity = $this->cartItems->getTotalQuantityForProduct($cart, $product);
        $this->assertStockAvailability($product, $currentQuantity + $quantity);

        if (null === $existing) {
            $item = new CartItem($cart, $product, $quantity, $resolvedRentalMonths);
            $cart->addItem($item);
            $this->persistence->persist($item);
        } else {
            $existing->increaseQuantity($quantity);
        }

        try {
            $cart->touch();
            $this->persistence->persist($cart);
            $this->persistence->flush();

            return $cart;
        } catch (UniqueConstraintViolationException $exception) {
            if (!$retryOnDuplicate) {
                throw $exception;
            }

            $cartToken = $cart->getToken();
            $productId = $product->getId();

            $this->persistence->clear();
            if (null === $productId) {
                throw $exception;
            }

            $freshCart = $this->findCartByToken($cartToken);
            $freshProduct = $this->persistence->findForUpdate(Product::class, $productId);

            if (null === $freshCart || null === $freshProduct) {
                throw $exception;
            }

            return $this->addProduct($freshCart->getToken(), $freshProduct, $quantity, $resolvedRentalMonths, false);
        }
    }

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
            $cart->touch();
            $this->persistence->persist($cart);
            $this->persistence->flush();
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
        $resolvedRentalMonths = null;
        if ('rental' === $product->getSellingType() && $quantity > 0) {
            $resolvedRentalMonths = $this->cartItems->determineRentalMonths($product, $rentalMonths, $existing);
        }

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
            if (null === $existing) {
                if ('rental' === $product->getSellingType() && null === $resolvedRentalMonths) {
                    throw new \InvalidArgumentException('Champ "rentalMonths" requis pour ce produit.');
                }

                $existing = new CartItem($cart, $product, $quantity, $resolvedRentalMonths);
                $cart->addItem($existing);
                $this->persistence->persist($existing);
            } else {
                $skipQuantityUpdate = false;

                if ('rental' === $product->getSellingType() && null !== $resolvedRentalMonths && $existing->getRentalMonths() !== $resolvedRentalMonths) {
                    $duplicate = $cart->getItemForProduct($product, $resolvedRentalMonths);
                    if (null !== $duplicate && $duplicate !== $existing) {
                        $duplicate->increaseQuantity($quantity);
                        $cart->removeItem($existing);
                        $this->persistence->remove($existing);
                        $skipQuantityUpdate = true;
                        $existing = $duplicate;
                    } else {
                        $existing->setRentalMonths($resolvedRentalMonths);
                    }
                }

                if (!$skipQuantityUpdate) {
                    $existing->setQuantity($quantity);
                }
            }
        }

        $cart->touch();
        $this->persistence->persist($cart);
        $this->persistence->flush();

        return $cart;
    }

    public function clearCart(?string $token): CartSession
    {
        $cart = $this->viewCart($token);

        foreach ($cart->getItems()->toArray() as $item) {
            $cart->removeItem($item);
            $this->persistence->remove($item);
        }

        $cart->touch();
        $this->persistence->persist($cart);
        $this->persistence->flush();

        return $cart;
    }

    public function findCartByToken(?string $token): ?CartSession
    {
        return $this->cartSessions->findByToken($token);
    }

    private function assertStockAvailability(Product $product, int $requestedQuantity): void
    {
        if ($requestedQuantity > $product->getStock()) {
            throw new \InvalidArgumentException('Stock insuffisant pour ce produit.');
        }
    }
}
