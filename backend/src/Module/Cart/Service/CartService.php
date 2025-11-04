<?php

declare(strict_types=1);

namespace App\Module\Cart\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Cart\Repository\CartItemRepository;
use App\Module\Cart\Repository\CartSessionRepository;
use App\Module\Catalog\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class CartService
{
    public function __construct(
        private readonly CartSessionRepository $cartSessions,
        private readonly CartItemRepository $cartItems,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function viewCart(?string $token): CartSession
    {
        $cart = $this->findCartByToken($token);

        if ($cart !== null) {
            return $cart;
        }

        return $this->createCart();
    }

    public function addProduct(?string $token, Product $product, int $quantity = 1): CartSession
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('La quantite doit etre superieure ou egale a 1.');
        }

        $cart = $this->viewCart($token);
        $existing = $cart->getItemForProduct($product);

        if ($existing === null) {
            $item = new CartItem($cart, $product, $quantity);
            $cart->addItem($item);
            $this->entityManager->persist($item);
        } else {
            $existing->increaseQuantity($quantity);
        }

        $cart->touch();
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    public function removeProduct(?string $token, Product $product): CartSession
    {
        $cart = $this->findCartByToken($token);

        if ($cart === null) {
            throw new InvalidArgumentException('Panier introuvable.');
        }

        $existing = $cart->getItemForProduct($product);

        if ($existing !== null) {
            $cart->removeItem($existing);
            $this->entityManager->remove($existing);
            $cart->touch();
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $cart;
    }

    public function updateProductQuantity(?string $token, Product $product, int $quantity): CartSession
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('La quantite doit etre superieure ou egale a 0.');
        }

        $cart = $this->viewCart($token);
        $existing = $cart->getItemForProduct($product);

        if ($quantity === 0) {
            if ($existing !== null) {
                $cart->removeItem($existing);
                $this->entityManager->remove($existing);
            }
        } else {
            if ($existing === null) {
                $existing = new CartItem($cart, $product, $quantity);
                $cart->addItem($existing);
                $this->entityManager->persist($existing);
            } else {
                $existing->setQuantity($quantity);
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
        if ($token === null || trim($token) === '') {
            return null;
        }

        return $this->cartSessions->findOneByToken(trim($token));
    }

    private function createCart(): CartSession
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while ($this->cartSessions->findOneByToken($token) !== null);

        $cart = new CartSession($token);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }
}
