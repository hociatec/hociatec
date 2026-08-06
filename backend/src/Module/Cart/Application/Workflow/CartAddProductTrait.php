<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

trait CartAddProductTrait
{
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
            $this->saveCart($cart);

            return $cart;
        } catch (UniqueConstraintViolationException $exception) {
            if (!$retryOnDuplicate) {
                throw $exception;
            }

            return $this->retryAddAfterDuplicate($cart, $product, $quantity, $resolvedRentalMonths, $exception);
        }
    }

    private function retryAddAfterDuplicate(CartSession $cart, Product $product, int $quantity, ?int $resolvedRentalMonths, UniqueConstraintViolationException $exception): CartSession
    {
        $cartToken = $cart->getToken();
        $productId = $product->getId();

        $this->cartSessions->clearUnitOfWork();
        if (null === $productId) {
            throw $exception;
        }

        $freshCart = $this->findCartByToken($cartToken);
        $freshProduct = $this->products->findForUpdate($productId);

        if (null === $freshCart || null === $freshProduct) {
            throw $exception;
        }

        return $this->addProduct($freshCart->getToken(), $freshProduct, $quantity, $resolvedRentalMonths, false);
    }
}
