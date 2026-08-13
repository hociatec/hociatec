<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\DTO\CartCheckoutResult;
use App\Module\Order\Application\Exception\CartCheckoutNotFoundException;
use App\Module\Order\Application\Exception\CheckoutRequestException;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;

final readonly class ExistingOrderCheckoutService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private ShippingAddressRepositoryPort $addresses,
        private StripeCheckoutService $stripeCheckout,
        private OrderAccessPolicy $accessPolicy,
    ) {
    }

    public function checkout(User $user, int $orderId, ?int $addressId, ?string $clientPlatform = null): CartCheckoutResult
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order || !$this->accessPolicy->canCheckout($user, $order)) {
            throw new CartCheckoutNotFoundException('Commande introuvable.');
        }

        if (Order::STATUS_CONFIRMED === $order->getStatus() || Order::STATUS_DELIVERED === $order->getStatus()) {
            return CartCheckoutResult::existingOrder($order);
        }

        if (Order::STATUS_PENDING !== $order->getStatus()) {
            throw CheckoutRequestException::orderCannotBePaid();
        }

        if ($order->getTotalPriceCents() <= 0 || $order->getItems()->isEmpty()) {
            throw CheckoutRequestException::orderHasNothingToPay();
        }

        $shipping = $this->resolveShippingAddress($user, $addressId);

        return CartCheckoutResult::redirect($this->stripeCheckout->createHostedCheckoutForOrder($user, $order, $shipping, $clientPlatform));
    }

    private function resolveShippingAddress(User $user, ?int $addressId): ShippingAddress
    {
        $shipping = null !== $addressId && $addressId > 0
            ? $this->addresses->findOneForUser($addressId, $user)
            : $this->addresses->findFirstForUser($user);

        if (!$shipping instanceof ShippingAddress) {
            throw CheckoutRequestException::missingCartShippingAddress();
        }

        return $shipping;
    }
}
