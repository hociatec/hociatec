<?php

declare(strict_types=1);

namespace App\Module\Cart\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Product;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class CartService
{
    public function __construct(
        private readonly CartSessionProvider $cartSessions,
        private readonly EntityManagerInterface $entityManager,
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
        $resolvedRentalMonths = $this->determineRentalMonths($product, $rentalMonths);
        $existing = $this->resolveExistingItem($cart, $product, $resolvedRentalMonths);

        $currentQuantity = $this->getTotalQuantityForProduct($cart, $product);
        $this->assertStockAvailability($product, $currentQuantity + $quantity);

        if (null === $existing) {
            $item = new CartItem($cart, $product, $quantity, $resolvedRentalMonths);
            $cart->addItem($item);
            $this->entityManager->persist($item);
        } else {
            $existing->increaseQuantity($quantity);
        }

        try {
            $cart->touch();
            $this->entityManager->persist($cart);
            $this->entityManager->flush();

            return $cart;
        } catch (UniqueConstraintViolationException $exception) {
            if (!$retryOnDuplicate) {
                throw $exception;
            }

            $cartToken = $cart->getToken();
            $productId = $product->getId();

            $this->entityManager->clear();
            if (null === $productId) {
                throw $exception;
            }

            $freshCart = $this->findCartByToken($cartToken);
            $freshProduct = $this->entityManager->find(Product::class, $productId);

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

        $existing = $this->resolveExistingItem($cart, $product, $rentalMonths);

        if (null !== $existing) {
            $cart->removeItem($existing);
            $this->entityManager->remove($existing);
            $cart->touch();
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
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
        $existing = $this->resolveExistingItem($cart, $product, $lookupMonths);
        $resolvedRentalMonths = null;
        if ('rental' === $product->getSellingType() && $quantity > 0) {
            $resolvedRentalMonths = $this->determineRentalMonths($product, $rentalMonths, $existing);
        }

        if ($quantity > 0) {
            $currentQuantity = $this->getTotalQuantityForProduct($cart, $product, $existing);
            $this->assertStockAvailability($product, $currentQuantity + $quantity);
        }

        if (0 === $quantity) {
            if (null !== $existing) {
                $cart->removeItem($existing);
                $this->entityManager->remove($existing);
            }
        } else {
            if (null === $existing) {
                if ('rental' === $product->getSellingType() && null === $resolvedRentalMonths) {
                    throw new \InvalidArgumentException('Champ "rentalMonths" requis pour ce produit.');
                }

                $existing = new CartItem($cart, $product, $quantity, $resolvedRentalMonths);
                $cart->addItem($existing);
                $this->entityManager->persist($existing);
            } else {
                $skipQuantityUpdate = false;

                if ('rental' === $product->getSellingType() && null !== $resolvedRentalMonths && $existing->getRentalMonths() !== $resolvedRentalMonths) {
                    $duplicate = $cart->getItemForProduct($product, $resolvedRentalMonths);
                    if (null !== $duplicate && $duplicate !== $existing) {
                        $duplicate->increaseQuantity($quantity);
                        $cart->removeItem($existing);
                        $this->entityManager->remove($existing);
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
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    public function clearCart(?string $token): CartSession
    {
        $cart = $this->viewCart($token);

        foreach ($cart->getItems()->toArray() as $item) {
            $cart->removeItem($item);
            $this->entityManager->remove($item);
        }

        $cart->touch();
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    public function findCartByToken(?string $token): ?CartSession
    {
        return $this->cartSessions->findByToken($token);
    }

    private function determineRentalMonths(Product $product, ?int $requestedMonths, ?CartItem $existingItem = null): ?int
    {
        if ('rental' !== $product->getSellingType()) {
            return null;
        }

        if (null === $requestedMonths) {
            $existingMonths = $existingItem?->getRentalMonths();

            if (null === $existingMonths) {
                throw new \InvalidArgumentException('Champ "rentalMonths" requis pour ce produit.');
            }

            return $existingMonths;
        }

        if ($requestedMonths < 1) {
            throw new \InvalidArgumentException('La durée de location doit être supérieure ou égale à 1 mois.');
        }

        return $requestedMonths;
    }

    private function resolveExistingItem(CartSession $cart, Product $product, ?int $rentalMonths = null): ?CartItem
    {
        if ('rental' !== $product->getSellingType()) {
            return $cart->getItemForProduct($product);
        }

        if (null !== $rentalMonths) {
            return $cart->getItemForProduct($product, $rentalMonths);
        }

        $items = $cart->getItemsForProduct($product);

        if (\count($items) > 1) {
            throw new \InvalidArgumentException('Plusieurs durées de location existent pour ce produit. Précisez "currentRentalMonths".');
        }

        return $items[0] ?? null;
    }

    private function assertStockAvailability(Product $product, int $requestedQuantity): void
    {
        if ($requestedQuantity > $product->getStock()) {
            throw new \InvalidArgumentException('Stock insuffisant pour ce produit.');
        }
    }

    private function getTotalQuantityForProduct(CartSession $cart, Product $product, ?CartItem $exclude = null): int
    {
        $total = 0;

        foreach ($cart->getItemsForProduct($product) as $item) {
            if (null !== $exclude && $item === $exclude) {
                continue;
            }

            $total += $item->getQuantity();
        }

        return $total;
    }
}
