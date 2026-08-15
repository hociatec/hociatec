<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

trait CartAddProductTrait
{
    public function addProduct(?string $token, Product $product, string|int $sellingTypeOrQuantity, int|\DateTimeImmutable|null $quantityOrRentalMonths = null, mixed $rentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null, bool $retryOnDuplicate = true): CartSession
    {
        if (is_int($sellingTypeOrQuantity)) {
            $legacyRentalMonths = $rentalMonths;
            $sellingType = $product->getSellingType();
            $quantity = $sellingTypeOrQuantity;
            $rentalMonths = is_int($quantityOrRentalMonths)
                ? $quantityOrRentalMonths
                : (is_int($rentalMonths) ? $rentalMonths : null);
            $rentalStartDate = $quantityOrRentalMonths instanceof \DateTimeImmutable
                ? $quantityOrRentalMonths
                : ($legacyRentalMonths instanceof \DateTimeImmutable ? $legacyRentalMonths : $rentalStartDate);
        } else {
            $sellingType = $sellingTypeOrQuantity;
            $quantity = is_int($quantityOrRentalMonths) ? $quantityOrRentalMonths : 1;
            $rentalMonths = is_int($rentalMonths) ? $rentalMonths : null;
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantite doit etre superieure ou egale a 1.');
        }

        $cart = $this->viewCart($token);
        $resolvedSellingType = $this->cartItems->normalizeSellingType($product, $sellingType);
        $resolvedRentalMonths = $this->cartItems->determineRentalMonths($product, $resolvedSellingType, $rentalMonths);
        $resolvedRentalStartDate = $this->cartItems->determineRentalStartDate($product, $resolvedSellingType, $rentalStartDate);
        $existing = $this->cartItems->resolveExistingItem($cart, $product, $resolvedSellingType, $resolvedRentalMonths, $resolvedRentalStartDate);

        $currentQuantity = $this->cartItems->getTotalQuantityForProduct($cart, $product, $resolvedSellingType);
        $this->assertStockAvailability($product, $currentQuantity + $quantity);

        if (null === $existing) {
            $item = new CartItem($cart, $product, $resolvedSellingType, $quantity, $resolvedRentalMonths, $resolvedRentalStartDate);
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

            return $this->retryAddAfterDuplicate($cart, $product, $resolvedSellingType, $quantity, $resolvedRentalMonths, $resolvedRentalStartDate, $exception);
        }
    }

    private function retryAddAfterDuplicate(CartSession $cart, Product $product, string $sellingType, int $quantity, ?int $resolvedRentalMonths, ?\DateTimeImmutable $resolvedRentalStartDate, UniqueConstraintViolationException $exception): CartSession
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

        return $this->addProduct($freshCart->getToken(), $freshProduct, $sellingType, $quantity, $resolvedRentalMonths, $resolvedRentalStartDate, false);
    }
}
