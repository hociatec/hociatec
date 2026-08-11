<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Provider;

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class CartSessionProvider
{
    public function __construct(
        private CartSessionRepositoryPort $cartSessions,
        private UnitOfWork $persistence,
    ) {
    }

    public function view(?string $token): CartSession
    {
        $cart = $this->findByToken($token);
        if (null === $cart) {
            return $this->create();
        }

        if (!$cart->isConverted()) {
            return $cart;
        }

        return $cart->getUser() instanceof User
            ? $this->createForUser($cart->getUser())
            : $this->create();
    }

    public function resolveForMutation(?string $token, ?User $user): CartSession
    {
        if (null !== $user) {
            $userCart = $this->cartSessions->findOneByUser($user);
            if (null !== $userCart) {
                return $userCart;
            }
        }

        $cart = $this->findByToken($token);
        if (null !== $cart && !$cart->isConverted()) {
            return $cart;
        }

        return null !== $user ? $this->createForUser($user) : $this->create();
    }

    public function findByToken(?string $token): ?CartSession
    {
        if (null === $token || '' === trim($token)) {
            return null;
        }

        return $this->cartSessions->findOneByToken(trim($token));
    }

    public function clearUnitOfWork(): void
    {
        $this->cartSessions->clearUnitOfWork();
    }

    private function create(): CartSession
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (null !== $this->cartSessions->findOneByToken($token));

        $cart = new CartSession($token);
        $this->persistence->persist($cart);
        $this->persistence->flush();

        return $cart;
    }

    private function createForUser(User $user): CartSession
    {
        $cart = $this->create();
        $cart->setUser($user);
        $this->persistence->persist($cart);
        $this->persistence->flush();

        return $cart;
    }
}
