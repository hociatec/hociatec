<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Application\Exception\CartAlreadyConvertedException;
use App\Module\Order\Application\Exception\CartCheckoutNotFoundException;
use App\Module\Order\Application\Exception\CheckoutRequestException;
use App\Module\Order\Application\DTO\CartOrderSummary;
use App\Module\Order\Application\DTO\OrderCreationData;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use Psr\Clock\ClockInterface;

final readonly class CartOrderCreator
{
    public function __construct(
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        private CartSubmittedOrderFactory $orders,
        private CartSessionRepositoryPort $carts,
        private CartOrderLineConverter $lineConverter,
        private CartOrderSummaryBuilder $summaryBuilder,
        private ClockInterface $clock,
    ) {
    }

    public function create(User $user, CartSession $cart, ShippingAddress $address): Order
    {
        if (0 === $cart->getItems()->count()) {
            throw CheckoutRequestException::emptyCart();
        }

        $summary = $this->summaryBuilder->build($cart, $user);

        return $this->transactions->transactional(
            function () use ($user, $cart, $address, $summary): Order {
                $cartId = $cart->getId();
                if (null === $cartId) {
                    throw new CartCheckoutNotFoundException('Panier introuvable.', 'CART_NOT_FOUND');
                }

                $lockedCart = $this->carts->findForUpdate($cartId);
                if (null === $lockedCart) {
                    throw new CartCheckoutNotFoundException('Panier introuvable.', 'CART_NOT_FOUND');
                }
                if ($lockedCart->isConverted()) {
                    throw new CartAlreadyConvertedException('Ce panier a deja ete valide.');
                }
                if (0 === $lockedCart->getItems()->count()) {
                    throw CheckoutRequestException::emptyCart();
                }

                $order = $this->createOrder($user, $address, $summary);
                $this->lineConverter->addLines($order, $lockedCart);
                $this->persistence->persist($order);
                $this->persistence->flush();

                if (null === $order->getId()) {
                    throw CheckoutRequestException::invalidOrder();
                }
                $lockedCart->markConverted($order->getId());
                $this->persistence->persist($lockedCart);
                $this->persistence->flush();

                return $order;
            },
        );
    }

    private function createOrder(User $user, ShippingAddress $address, CartOrderSummary $summary): Order
    {
        return $this->orders->create(new OrderCreationData($user, $address, $summary, $this->clock->now()));
    }
}
