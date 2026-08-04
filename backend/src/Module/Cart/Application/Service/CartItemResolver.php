<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Service;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class CartItemResolver
{
    public function determineRentalMonths(Product $product, ?int $requestedMonths, ?CartItem $existingItem = null): ?int
    {
        if ('rental' !== $product->getSellingType()) {
            return null;
        }

        if (null === $requestedMonths) {
            $existingMonths = $existingItem?->getRentalMonths();

            if (null === $existingMonths) {
                throw new \InvalidArgumentException('Champ "rentalMonths" requis pour ce produit.');
            }

            return $existingMonths;
        }

        if ($requestedMonths < 1) {
            throw new \InvalidArgumentException('La durée de location doit être supérieure ou égale à 1 mois.');
        }

        return $requestedMonths;
    }

    public function resolveExistingItem(CartSession $cart, Product $product, ?int $rentalMonths = null): ?CartItem
    {
        if ('rental' !== $product->getSellingType()) {
            return $cart->getItemForProduct($product);
        }

        if (null !== $rentalMonths) {
            return $cart->getItemForProduct($product, $rentalMonths);
        }

        $items = $cart->getItemsForProduct($product);

        if (\count($items) > 1) {
            throw new \InvalidArgumentException('Plusieurs durées de location existent pour ce produit. Précisez "currentRentalMonths".');
        }

        return $items[0] ?? null;
    }

    public function getTotalQuantityForProduct(CartSession $cart, Product $product, ?CartItem $exclude = null): int
    {
        $total = 0;

        foreach ($cart->getItemsForProduct($product) as $item) {
            if (null !== $exclude && $item === $exclude) {
                continue;
            }

            $total += $item->getQuantity();
        }

        return $total;
    }
}
