<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Service\VoucherEngine;

final readonly class CartVoucherService
{
    public function __construct(
        private CartSessionProvider $carts,
        private VoucherEngine $voucherEngine,
        private DoctrineUnitOfWork $persistence,
    ) {
    }

    public function apply(?string $token, string $voucherCode, ?User $user = null): CartSession
    {
        $cart = $this->carts->resolveForMutation($token, $user);
        $summary = $this->voucherEngine->calculateCartSummary($cart, $user, $voucherCode);
        $status = $summary['voucherCodeStatus'];

        if ('applied' !== $status) {
            throw new \InvalidArgumentException('ineligible' === $status ? 'Ce bon de réduction n\'est pas éligible pour ce panier.' : 'Bon de réduction invalide.');
        }

        return $this->save($cart->setVoucherCode($voucherCode));
    }

    public function clear(?string $token, ?User $user = null): CartSession
    {
        return $this->save(
            $this->carts->resolveForMutation($token, $user)->setVoucherCode(null),
        );
    }

    private function save(CartSession $cart): CartSession
    {
        $this->persistence->persist($cart);
        $this->persistence->commit();

        return $cart;
    }
}
