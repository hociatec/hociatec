<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Workflow;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;

final readonly class VoucherNotificationContextBuilder
{
    public function __construct(private string $frontendUrl)
    {
    }

    /** @return array<string, string> */
    public function build(User $user, Voucher $voucher): array
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $valueLabel = Voucher::TYPE_PERCENT === $voucher->getDiscountType()
            ? $voucher->getDiscountValue().'%'
            : number_format($voucher->getDiscountValue() / 100, 2, ',', ' ').' EUR';
        $displayTimezone = new \DateTimeZone('Europe/Paris');

        return [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'app_frontend_url' => $frontendUrl,
            'voucher_name' => $voucher->getName(),
            'voucher_code' => $voucher->getCode(),
            'voucher_description' => (string) ($voucher->getDescription() ?? ''),
            'voucher_discount_type' => $voucher->getDiscountType(),
            'voucher_discount_value' => (string) $voucher->getDiscountValue(),
            'voucher_value_label' => $valueLabel,
            'voucher_starts_at' => $voucher->getStartsAt()?->setTimezone($displayTimezone)->format('d/m/Y à H:i') ?? '',
            'voucher_ends_at' => $voucher->getEndsAt()?->setTimezone($displayTimezone)->format('d/m/Y à H:i') ?? '',
            'voucher_is_active' => $voucher->isActive() ? '1' : '0',
            'shop_url' => $frontendUrl.'/boutique',
            'cart_url' => $frontendUrl.'/panier',
        ];
    }
}
