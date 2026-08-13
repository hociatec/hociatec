<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Cart\Application\Workflow\CartSessionWorkflow;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Application\DTO\CartCheckoutResult;
use App\Module\Order\Application\Exception\CartAlreadyConvertedException;
use App\Module\Order\Application\Exception\CartCheckoutConflictException;
use App\Module\Order\Application\Exception\CartCheckoutNotFoundException;
use App\Module\Order\Application\Exception\CheckoutRequestException;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;

final readonly class CartCheckoutService
{
    public function __construct(
        private StripeCheckoutService $stripe,
        private OrderRepositoryPort $orders,
        private CartSessionWorkflow $carts,
        private ShippingAddressRepositoryPort $addresses,
    ) {
    }

    public function checkout(User $user, string $cartToken, ?int $addressId, ?string $clientPlatform = null): CartCheckoutResult
    {
        $cart = $this->carts->findCartByToken($cartToken);
        if (!$cart instanceof CartSession) {
            throw new CartCheckoutNotFoundException('Panier introuvable.');
        }

        if ($cart->isConverted()) {
            return $this->convertedResult($cart, $user);
        }
        if (0 === $cart->getItems()->count()) {
            throw CheckoutRequestException::emptyCart();
        }

        $address = $this->resolveAddress($user, $addressId);
        try {
            return CartCheckoutResult::redirect($this->stripe->createHostedCheckout($user, $cart, $address, $clientPlatform));
        } catch (CartAlreadyConvertedException) {
            return $this->convertedResult($cart, $user);
        }
    }

    private function convertedResult(CartSession $cart, User $user): CartCheckoutResult
    {
        $order = $this->resolveConvertedOrder($cart->getConvertedOrderId(), $user);
        if (!$order instanceof Order) {
            throw new CartCheckoutConflictException('Cette commande a déjà été validée.');
        }

        return CartCheckoutResult::existingOrder($order);
    }

    private function resolveConvertedOrder(?int $orderId, User $user): ?Order
    {
        if (null === $orderId) {
            return null;
        }

        $order = $this->orders->find($orderId);

        return $order instanceof Order && $order->getUser()->getId() === $user->getId() ? $order : null;
    }

    private function resolveAddress(User $user, ?int $addressId): ShippingAddress
    {
        if (null !== $addressId && $addressId > 0) {
            $address = $this->addresses->findOneForUser($addressId, $user);
            if (!$address instanceof ShippingAddress) {
                throw CheckoutRequestException::invalidCartShippingAddress();
            }

            return $address;
        }

        $address = $this->addresses->findFirstForUser($user);
        if (!$address instanceof ShippingAddress) {
            throw CheckoutRequestException::missingCartShippingAddress();
        }

        return $address;
    }
}
