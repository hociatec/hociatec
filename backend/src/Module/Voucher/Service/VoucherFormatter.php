<?php

declare(strict_types=1);

namespace App\Module\Voucher\Service;

use App\Module\Voucher\Entity\Voucher;

final class VoucherFormatter
{
    private function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatVoucher(Voucher $voucher): array
    {
        return [
            'id' => $voucher->getId(),
            'name' => $voucher->getName(),
            'code' => $voucher->getCode(),
            'description' => $voucher->getDescription(),
            'discountType' => $voucher->getDiscountType(),
            'discountValue' => $voucher->getDiscountValue(),
            'isActive' => $voucher->isActive(),
            'startsAt' => $voucher->getStartsAt()?->format(DATE_ATOM),
            'endsAt' => $voucher->getEndsAt()?->format(DATE_ATOM),
            'createdAt' => $voucher->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $voucher->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
