<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Service;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherLookupInterface;
use Psr\Clock\ClockInterface;

final class VoucherEngine
{
    public function __construct(
        private readonly VoucherLookupInterface $vouchers,
        private readonly ?ClockInterface $clock = null,
    ) {
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
            if ($item->getQuantity() <= 0) {
                throw new \InvalidArgumentException('La quantite doit etre superieure ou egale a 1.');
            }

            $linePrice = $item->getProduct()->getPriceCents() * $item->getQuantity();
            if ('rental' === $item->getProduct()->getSellingType()) {
                $storedRentalMonths = $item->getStoredRentalMonths();
                if (0 === $storedRentalMonths || $storedRentalMonths < -1) {
                    throw new \InvalidArgumentException('La duree de location doit etre superieure ou egale a 1 mois.');
                }

                $rentalMonths = $item->getRentalMonths() ?? 1;
                $linePrice *= $rentalMonths;
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
        if ($subtotalPriceCents < 0) {
            throw new \InvalidArgumentException('Le sous-total ne peut pas etre negatif.');
        }

        $now = $this->clock?->now() ?? new \DateTimeImmutable();
        $enteredVoucherCode = Voucher::normalizeCode($voucherCode);
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
                        $appliedVoucher = VoucherFormatter::formatCartVoucher($voucher, $discountAmount);
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
            'enteredVoucherCode' => '' !== $enteredVoucherCode ? $enteredVoucherCode : null,
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
        if (!$voucher->canBeUsedBy($user, $now)) {
            return false;
        }

        return $subtotalPriceCents > 0;
    }
}
