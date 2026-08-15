<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Rental\Projection;

use App\Module\Order\Application\Projection\OrderStatusLabelFormatter;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rental\Application\Projection\RentalFormatter;

final readonly class AdminRentalFormatter
{
    public function __construct(
        private RentalFormatter $rentals,
        private OrderStatusLabelFormatter $orderLabels,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function format(OrderItem $item, ?\DateTimeImmutable $today = null): array
    {
        $payload = $this->rentals->format($item, $today);
        $order = $item->getOrder();
        $user = $order?->getUser();

        $payload['orderStatus'] = $order?->getStatus();
        $payload['orderStatusLabel'] = null !== $order ? $this->orderLabels->status($order->getStatus()) : null;
        $payload['customer'] = [
            'id' => $user?->getId(),
            'email' => $user?->getEmail(),
            'firstName' => $user?->getFirstName(),
            'lastName' => $user?->getLastName(),
        ];
        $payload['allowedAdminActions'] = $this->allowedAdminActions($item);

        return $payload;
    }

    /**
     * @return list<string>
     */
    private function allowedAdminActions(OrderItem $item): array
    {
        if ('pending' !== $item->getRentalRequestStatus()) {
            return [];
        }

        return match ($item->getRentalRequestType()) {
            'extend' => ['approve_extension', 'reject_request'],
            'end_early' => ['approve_end_early', 'reject_request'],
            default => ['reject_request'],
        };
    }
}
