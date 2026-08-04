<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Service;

use App\Module\Voucher\Domain\Entity\Voucher;

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
            'recipientUserId' => $voucher->getRecipientUserId(),
            'recipientEmail' => $voucher->getRecipientEmail(),
            'sentAt' => $voucher->getSentAt()?->format(DATE_ATOM),
            'createdAt' => $voucher->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $voucher->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatCartVoucher(Voucher $voucher, int $discountAmountCents): array
    {
        return [
            'id' => $voucher->getId(),
            'name' => $voucher->getName(),
            'code' => $voucher->getCode(),
            'description' => $voucher->getDescription(),
            'discountType' => $voucher->getDiscountType(),
            'discountValue' => $voucher->getDiscountValue(),
            'discountAmountCents' => $discountAmountCents,
        ];
    }
}
