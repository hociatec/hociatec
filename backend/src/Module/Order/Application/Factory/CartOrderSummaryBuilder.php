<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Calculator\VoucherEngine;

final readonly class CartOrderSummaryBuilder
{
    public function __construct(
        private PromotionEngine $promotionEngine,
        private VoucherEngine $voucherEngine,
    ) {
    }

    /** @return array<string, mixed> */
    public function build(CartSession $cart, User $user): array
    {
        $promotion = $this->promotionEngine->calculateCartSummary($cart, $user);
        $voucher = $this->voucherEngine->calculateCartSummary($cart, $user, $cart->getVoucherCode());

        return null !== $cart->getVoucherCode() && 'applied' === $voucher['voucherCodeStatus']
            ? $voucher
            : $promotion;
    }
}
