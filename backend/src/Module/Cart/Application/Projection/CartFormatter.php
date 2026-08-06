<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Projection;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Application\Calculator\CartTotalsCalculator;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Calculator\VoucherEngine;

final class CartFormatter
{
    public function __construct(
        private readonly PromotionEngine $promotionEngine,
        private readonly VoucherEngine $voucherEngine,
        private readonly CatalogFormatter $catalogFormatter,
        private readonly ?CartTotalsCalculator $totalsCalculator = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCart(CartSession $cart, ?User $user = null): array
    {
        $totals = ($this->totalsCalculator ?? new CartTotalsCalculator())->calculate($cart);
        $items = [];
        foreach ($totals['items'] as $line) {
            $item = $line['item'];
            $items[] = [
                'id' => $item->getId(),
                'product' => $this->catalogFormatter->formatProduct($item->getProduct()),
                'quantity' => $line['quantity'],
                'linePriceCents' => $line['linePriceCents'],
                'rentalMonths' => $line['rentalMonths'],
            ];
        }
        $subtotalPriceCents = $totals['subtotalPriceCents'];

        $promotionSummary = [
            'subtotalPriceCents' => $subtotalPriceCents,
            'discountAmountCents' => 0,
            'totalPriceCents' => $subtotalPriceCents,
            'appliedPromotion' => null,
            'eligiblePromotions' => [],
        ];

        try {
            $promotionSummary = $this->promotionEngine->calculateCartSummary($cart, $user);
        } catch (\RuntimeException) {
        } catch (\LogicException) {
        }

        $voucherSummary = [
            'subtotalPriceCents' => $subtotalPriceCents,
            'discountAmountCents' => 0,
            'totalPriceCents' => $subtotalPriceCents,
            'appliedVoucher' => null,
            'voucherCodeStatus' => 'none',
            'enteredVoucherCode' => null,
        ];

        try {
            $voucherSummary = $this->voucherEngine->calculateCartSummary($cart, $user);
        } catch (\RuntimeException) {
        } catch (\LogicException) {
        }

        $summary = (null !== $cart->getVoucherCode() && 'applied' === $voucherSummary['voucherCodeStatus'])
            ? $voucherSummary
            : [
                'subtotalPriceCents' => $promotionSummary['subtotalPriceCents'],
                'discountAmountCents' => $promotionSummary['discountAmountCents'],
                'totalPriceCents' => $promotionSummary['totalPriceCents'],
                'appliedPromotion' => $promotionSummary['appliedPromotion'],
                'eligiblePromotions' => $promotionSummary['eligiblePromotions'],
            ];

        return [
            'token' => $cart->getToken(),
            'items' => $items,
            'totalQuantity' => $totals['totalQuantity'],
            'subtotalPriceCents' => $summary['subtotalPriceCents'],
            'discountAmountCents' => $summary['discountAmountCents'],
            'totalPriceCents' => $summary['totalPriceCents'],
            'appliedPromotion' => $promotionSummary['appliedPromotion'],
            'eligiblePromotions' => $promotionSummary['eligiblePromotions'],
            'appliedVoucher' => $voucherSummary['appliedVoucher'] ?? null,
            'enteredVoucherCode' => $voucherSummary['enteredVoucherCode'] ?? null,
            'voucherCodeStatus' => $voucherSummary['voucherCodeStatus'],
            'updatedAt' => $cart->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
