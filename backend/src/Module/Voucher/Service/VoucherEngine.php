<?php

declare(strict_types=1);

namespace App\Module\Voucher\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\User\Entity\User;
use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Repository\VoucherLookupInterface;

final class VoucherEngine
{
    public function __construct(private readonly VoucherLookupInterface $vouchers)
    {
    }

    /**
     * @return array{
     *   subtotalPriceCents:int,
     *   discountAmountCents:int,
     *   totalPriceCents:int,
     *   appliedVoucher: array<string,mixed>|null,
     *   voucherCodeStatus: string,
     *   enteredVoucherCode: string|null
     * }
     */
    public function calculateCartSummary(CartSession $cart, ?User $user, ?string $voucherCode = null): array
    {
        $subtotal = 0;

        foreach ($cart->getItems() as $item) {
            $linePrice = $item->getProduct()->getPriceCents() * $item->getQuantity();
            if ('rental' === $item->getProduct()->getSellingType()) {
                $linePrice *= max(1, $item->getRentalMonths() ?? 1);
            }

            $subtotal += $linePrice;
        }

        return $this->calculateForSubtotal($subtotal, $user, $voucherCode ?? $cart->getVoucherCode());
    }

    /**
     * @return array{
     *   subtotalPriceCents:int,
     *   discountAmountCents:int,
     *   totalPriceCents:int,
     *   appliedVoucher: array<string,mixed>|null,
     *   voucherCodeStatus: string,
     *   enteredVoucherCode: string|null
     * }
     */
    public function calculateForSubtotal(int $subtotalPriceCents, ?User $user, ?string $voucherCode = null): array
    {
        $now = new \DateTimeImmutable();
        $enteredVoucherCode = is_string($voucherCode) ? trim($voucherCode) : '';
        $status = 'none';
        $appliedVoucher = null;
        $discountAmount = 0;

        if ('' !== $enteredVoucherCode) {
            $status = 'invalid';
            $voucher = $this->vouchers->findOneByCode($enteredVoucherCode);
            if (null !== $voucher) {
                if ($this->isVoucherEligible($voucher, $subtotalPriceCents, $now, $user)) {
                    $discountAmount = $this->computeDiscountAmount($voucher, $subtotalPriceCents);
                    if ($discountAmount > 0) {
                        $appliedVoucher = [
                            ...VoucherFormatter::formatVoucher($voucher),
                            'discountAmountCents' => $discountAmount,
                        ];
                        $status = 'applied';
                    } else {
                        $status = 'ineligible';
                    }
                } else {
                    $status = 'ineligible';
                }
            }
        }

        return [
            'subtotalPriceCents' => $subtotalPriceCents,
            'discountAmountCents' => $discountAmount,
            'totalPriceCents' => max(0, $subtotalPriceCents - $discountAmount),
            'appliedVoucher' => $appliedVoucher,
            'voucherCodeStatus' => $status,
            'enteredVoucherCode' => '' !== $enteredVoucherCode ? mb_strtoupper($enteredVoucherCode) : null,
        ];
    }

    private function computeDiscountAmount(Voucher $voucher, int $subtotalPriceCents): int
    {
        if ($subtotalPriceCents <= 0) {
            return 0;
        }

        if (Voucher::TYPE_PERCENT === $voucher->getDiscountType()) {
            $percent = max(0, min(100, $voucher->getDiscountValue()));

            return min($subtotalPriceCents, (int) round($subtotalPriceCents * ($percent / 100)));
        }

        return min($subtotalPriceCents, max(0, $voucher->getDiscountValue()));
    }

    private function isVoucherEligible(Voucher $voucher, int $subtotalPriceCents, \DateTimeImmutable $now, ?User $user): bool
    {
        if (!$voucher->isActive()) {
            return false;
        }

        if (null !== $voucher->getStartsAt() && $voucher->getStartsAt() > $now) {
            return false;
        }

        if (null !== $voucher->getEndsAt() && $voucher->getEndsAt() < $now) {
            return false;
        }

        if (!$this->isRecipientEligible($voucher, $user)) {
            return false;
        }

        return $subtotalPriceCents > 0;
    }

    private function isRecipientEligible(Voucher $voucher, ?User $user): bool
    {
        if (null === $voucher->getRecipientUserId() && null === $voucher->getRecipientEmail()) {
            return true;
        }

        if (null === $user) {
            return false;
        }

        if (null !== $voucher->getRecipientUserId() && $voucher->getRecipientUserId() === $user->getId()) {
            return true;
        }

        return null !== $voucher->getRecipientEmail() && mb_strtolower($voucher->getRecipientEmail()) === mb_strtolower($user->getEmail());
    }
}
