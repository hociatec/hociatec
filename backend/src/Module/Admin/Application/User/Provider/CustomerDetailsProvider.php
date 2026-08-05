<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\User\Provider;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Application\Projection\VoucherFormatter;

final readonly class CustomerDetailsProvider
{
    public function __construct(
        private UserRepositoryPort $users,
        private ShippingAddressRepositoryPort $addresses,
        private OrderRepositoryPort $orders,
        private VoucherRepositoryPort $vouchers,
        private OrderFormatter $orderFormatter,
        private ShippingAddressFormatter $shippingAddressFormatter,
        private VoucherFormatter $voucherFormatter,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function details(int $userId): ?array
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return null;
        }

        $orders = $this->orders->findByUser($user);
        $totalSpentCents = 0;
        $lastOrderAt = null;
        $lastOrderNumber = null;

        foreach ($orders as $index => $order) {
            $totalSpentCents += $order->getTotalPriceCents();
            if (0 === $index) {
                $lastOrderAt = $order->getCreatedAt()->format(DATE_ATOM);
                $lastOrderNumber = $order->getNumber();
            }
        }

        return [
            'customer' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'phoneNumber' => $user->getPhoneNumber(),
                'isVerified' => $user->isVerified(),
                'adminNotes' => $user->getAdminNotes(),
                'adminTags' => $user->getAdminTags(),
                'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
                'ordersCount' => count($orders),
                'totalSpentCents' => $totalSpentCents,
                'lastOrderAt' => $lastOrderAt,
                'lastOrderNumber' => $lastOrderNumber,
            ],
            'addresses' => array_map(
                fn ($address): array => $this->shippingAddressFormatter->toArray($address),
                $this->addresses->findAllForUser($user),
            ),
            'orders' => array_map(
                fn ($order): array => $this->orderFormatter->formatOrder($order),
                $orders,
            ),
            'vouchers' => array_map(
                fn ($voucher): array => $this->voucherFormatter->formatVoucher($voucher),
                $this->vouchers->findByRecipientUserId($userId),
            ),
        ];
    }
}
