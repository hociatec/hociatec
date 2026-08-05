<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final class CartMergeService
{
    public function __construct(
        private readonly CartSessionRepositoryPort $carts,
        private readonly DoctrineUnitOfWork $persistence,
    ) {
    }

    public function mergeForUser(?string $token, User $user): CartSession
    {
        $userCart = $this->carts->findOneByUser($user);
        $tokenCart = null !== $token && '' !== $token ? $this->carts->findOneByToken($token) : null;

        if ($userCart && $tokenCart && $userCart->getId() !== $tokenCart->getId()) {
            // Merge token cart into user's cart
            foreach ($tokenCart->getItems() as $item) {
                $rentalMonths = 'rental' === $item->getProduct()->getSellingType() ? $item->getRentalMonths() : null;
                $existing = $userCart->getItemForProduct($item->getProduct(), $rentalMonths);
                if ($existing) {
                    $existing->increaseQuantity($item->getQuantity());
                    $this->persistence->remove($item);
                } else {
                    $userCart->addItem($item);
                }
            }
            $userCart->touch();
            $this->persistence->persist($userCart);
            // Remove the old cart shell
            $this->persistence->remove($tokenCart);
            $this->persistence->commit();

            return $userCart;
        }

        if ($userCart && !$tokenCart) {
            return $userCart;
        }

        if (!$userCart && $tokenCart) {
            $tokenCart->setUser($user);
            $tokenCart->touch();
            $this->persistence->persist($tokenCart);
            $this->persistence->commit();

            return $tokenCart;
        }

        // Neither exists: create an empty cart for user for consistency
        if (!$userCart) {
            $userCart = $this->createCartForUser($user);
        }

        return $userCart;
    }

    private function createCartForUser(User $user): CartSession
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (null !== $this->carts->findOneByToken($token));

        $cart = new CartSession($token);
        $cart->setUser($user);
        $this->persistence->persist($cart);
        $this->persistence->commit();

        return $cart;
    }
}
