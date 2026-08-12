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
    private const ORDER_FILTERS = ['all', 'open', 'delivered', 'cancelled'];

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
    public function details(int $userId, string $orderStatus = 'all', int $orderPage = 1, int $orderPerPage = 10, ?string $orderQuery = null): ?array
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return null;
        }

        $orderStatus = in_array($orderStatus, self::ORDER_FILTERS, true) ? $orderStatus : 'all';
        $orderPage = max(1, $orderPage);
        $orderPerPage = max(1, min(100, $orderPerPage));
        $allOrders = $this->orders->findByUser($user, 1000, 0);
        $totalSpentCents = 0;
        $lastOrderAt = null;
        $lastOrderNumber = null;

        foreach ($allOrders as $index => $order) {
            $totalSpentCents += $order->getTotalPriceCents();
            if (0 === $index) {
                $lastOrderAt = $order->getCreatedAt()->format(DATE_ATOM);
                $lastOrderNumber = $order->getNumber();
            }
        }

        $ordersTotal = $this->orders->countForUserList($user, $orderStatus, $orderQuery);
        $ordersOffset = ($orderPage - 1) * $orderPerPage;
        $orders = $this->orders->findForUserList($user, $orderStatus, $orderQuery, $orderPerPage, $ordersOffset);
        $ordersTotalPages = max(1, (int) ceil($ordersTotal / $orderPerPage));
        $orderCounts = $this->orders->countStatusBucketsForUser($user);

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
                'ordersCount' => $orderCounts['all'],
                'totalSpentCents' => $totalSpentCents,
                'lastOrderAt' => $lastOrderAt,
                'lastOrderNumber' => $lastOrderNumber,
            ],
            'addresses' => array_map(
                fn ($address): array => $this->shippingAddressFormatter->toArray($address),
                $this->addresses->findAllForUser($user),
            ),
            'orders' => [
                'items' => array_map(
                    fn ($order): array => $this->orderFormatter->formatOrder($order),
                    $orders,
                ),
                'meta' => [
                    'page' => $orderPage,
                    'perPage' => $orderPerPage,
                    'total' => $ordersTotal,
                    'totalPages' => $ordersTotalPages,
                ],
                'stats' => $orderCounts,
                'filter' => $orderStatus,
                'query' => null !== $orderQuery ? trim($orderQuery) : '',
            ],
            'vouchers' => array_map(
                fn ($voucher): array => $this->voucherFormatter->formatVoucher($voucher),
                $this->vouchers->findByRecipientUserId($userId),
            ),
        ];
    }
}
