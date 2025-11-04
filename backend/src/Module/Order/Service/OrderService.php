<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Product;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\User\Entity\User;
use App\Module\User\Entity\ShippingAddress;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Module\Order\Message\OrderCreatedMessage;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderNumberGenerator $numberGenerator,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function createFromCart(User $user, CartSession $cart): Order
    {
        throw new InvalidArgumentException('Adresse de livraison requise. Utiliser createFromCartWithAddress().');
    }

    public function createFromCartWithAddress(User $user, CartSession $cart, ShippingAddress $address): Order
    {
        if ($cart->getItems()->count() === 0) {
            throw new InvalidArgumentException('Le panier est vide.');
        }

        $order = $this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($user, $cart, $address): Order {
            $order = new Order($this->numberGenerator->generate(), $user);

            $order
                ->setStatus(Order::STATUS_PENDING)
                ->setShippingName($address->getName())
                ->setShippingAddress($address->getAddress())
                ->setShippingPostalCode($address->getPostalCode())
                ->setShippingCity($address->getCity());

            $total = 0;

            foreach ($cart->getItems() as $cartItem) {
                $product = $cartItem->getProduct();
                $productId = $product->getId();
                if ($productId === null) {
                    throw new InvalidArgumentException('Produit invalide.');
                }

                /** @var Product|null $lockedProduct */
                $lockedProduct = $em->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
                if ($lockedProduct === null) {
                    throw new InvalidArgumentException('Produit introuvable.');
                }

                $quantity = $cartItem->getQuantity();
                $currentStock = $lockedProduct->getStock();
                if ($quantity > $currentStock) {
                    throw new InvalidArgumentException('Stock insuffisant pour le produit ' . $lockedProduct->getSku() . '.');
                }

                $lockedProduct->setStock($currentStock - $quantity);

                $unitPrice = $lockedProduct->getPriceCents();
                $line = $unitPrice * $quantity;

                $item = new OrderItem($lockedProduct->getName(), $lockedProduct->getSku(), $unitPrice, $quantity);
                $item->setProduct($lockedProduct);
                $order->addItem($item);
                $em->persist($item);

                $total += $line;
            }

            $order->setTotalPriceCents($total);
            $em->persist($order);
            $em->flush();

            return $order;
        });

        $this->bus->dispatch(new OrderCreatedMessage($order->getId() ?? 0, $order->getNumber(), $user->getId() ?? 0));

        return $order;
    }
}
