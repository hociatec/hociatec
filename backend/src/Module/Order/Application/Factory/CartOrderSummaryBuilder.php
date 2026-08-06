<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Application\DTO\CartOrderSummary;
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

    public function build(CartSession $cart, User $user): CartOrderSummary
    {
        $promotion = $this->promotionEngine->calculateCartSummary($cart, $user);
        $voucher = $this->voucherEngine->calculateCartSummary($cart, $user, $cart->getVoucherCode());

        $summary = null !== $cart->getVoucherCode() && 'applied' === $voucher['voucherCodeStatus']
            ? $voucher
            : $promotion;

        return CartOrderSummary::fromArray($summary);
    }
}
