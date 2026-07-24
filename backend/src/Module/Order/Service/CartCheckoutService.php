<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\Cart\Service\CartService;
use App\Module\Order\DTO\CartCheckoutResult;
use App\Module\Order\Entity\Order;
use App\Module\Order\Exception\CartCheckoutConflictException;
use App\Module\Order\Exception\CartCheckoutNotFoundException;
use App\Module\Order\Repository\OrderRepository;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;

final readonly class CartCheckoutService
{
    public function __construct(
        private StripeCheckoutService $stripe,
        private OrderRepository $orders,
        private CartService $carts,
        private ShippingAddressRepository $addresses,
    ) {
    }

    public function checkout(User $user, string $cartToken, ?int $addressId): CartCheckoutResult
    {
        $cart = $this->carts->findCartByToken($cartToken);
        if (!$cart instanceof CartSession) {
            throw new CartCheckoutNotFoundException('Panier introuvable.');
        }

        if ($cart->isConverted()) {
            return $this->convertedResult($cart, $user);
        }
        if (0 === $cart->getItems()->count()) {
            throw new \InvalidArgumentException('Le panier est vide.');
        }

        $address = $this->resolveAddress($user, $addressId);
        try {
            return CartCheckoutResult::redirect($this->stripe->createHostedCheckout($user, $cart, $address));
        } catch (\InvalidArgumentException $exception) {
            if ('Ce panier a deja ete valide.' === $exception->getMessage()) {
                return $this->convertedResult($cart, $user);
            }

            throw $exception;
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
                throw new \InvalidArgumentException('Adresse de livraison invalide.');
            }

            return $address;
        }

        $address = $this->addresses->findFirstForUser($user);
        if (!$address instanceof ShippingAddress) {
            throw new \InvalidArgumentException('Aucune adresse de livraison trouvée.');
        }

        return $address;
    }
}
