<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Shared\Application\UnitOfWork;

final readonly class CartOrderLineConverter
{
    public function __construct(
        private ProductCatalogRepository $products,
        private UnitOfWork $persistence,
    ) {
    }

    public function addLines(Order $order, CartSession $cart): void
    {
        foreach ($cart->getItems() as $cartItem) {
            $productId = $cartItem->getProduct()->getId();
            if (null === $productId) {
                throw new \InvalidArgumentException('Produit invalide.');
            }

            $product = $this->products->findForUpdate($productId);
            if (null === $product) {
                throw new \InvalidArgumentException('Produit introuvable.');
            }

            $quantity = $cartItem->getQuantity();
            if ($quantity > $product->getStock()) {
                throw new \InvalidArgumentException('Stock insuffisant pour le produit '.$product->getSku().'.');
            }
            $product->reserveStock($quantity);

            $item = (new OrderItem($product->getName(), $product->getSku(), $product->getPriceCents(), $quantity))
                ->setProduct($product)
                ->setVatRateBps(2000);
            $order->addItem($item);
            $this->persistence->persist($item);
        }
    }
}
