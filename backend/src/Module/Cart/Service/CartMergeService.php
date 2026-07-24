<?php

declare(strict_types=1);

namespace App\Module\Cart\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\Cart\Repository\CartSessionRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class CartMergeService
{
    public function __construct(
        private readonly CartSessionRepository $carts,
        private readonly EntityManagerInterface $em,
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
                    $this->em->remove($item);
                } else {
                    $userCart->addItem($item);
                }
            }
            $userCart->touch();
            $this->em->persist($userCart);
            // Remove the old cart shell
            $this->em->remove($tokenCart);
            $this->em->flush();

            return $userCart;
        }

        if ($userCart && !$tokenCart) {
            return $userCart;
        }

        if (!$userCart && $tokenCart) {
            $tokenCart->setUser($user);
            $tokenCart->touch();
            $this->em->persist($tokenCart);
            $this->em->flush();

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
        $this->em->persist($cart);
        $this->em->flush();

        return $cart;
    }
}
