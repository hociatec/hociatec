<?php

declare(strict_types=1);

namespace App\Module\Admin\User\Controller;

use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\ShippingAddressFormatter;
use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Service\VoucherFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}', name: 'api_admin_customers_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ShowCustomerController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ShippingAddressRepository $addresses,
        private readonly OrderRepository $orders,
        private readonly VoucherRepository $vouchers,
    ) {
    }

    public function __invoke(int $userId): JsonResponse
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return ApiResponse::error('Client introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $orders = $this->orders->findByUser($user);
        $addressRows = array_map(
            static fn ($address): array => ShippingAddressFormatter::toArray($address),
            $this->addresses->findAllForUser($user),
        );

        $totalSpentCents = 0;
        $lastOrderAt = null;
        $lastOrderNumber = null;

        foreach ($orders as $index => $order) {
            $totalSpentCents += $order->getTotalPriceCents();
            if ($index === 0) {
                $lastOrderAt = $order->getCreatedAt()->format(DATE_ATOM);
                $lastOrderNumber = $order->getNumber();
            }
        }

        return ApiResponse::success([
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
            'addresses' => $addressRows,
            'orders' => array_map(
                static fn ($order): array => OrderFormatter::formatOrder($order),
                $orders,
            ),
            'vouchers' => array_map(
                static fn ($voucher): array => VoucherFormatter::formatVoucher($voucher),
                $this->vouchers->findByRecipientUserId($userId),
            ),
        ]);
    }
}
