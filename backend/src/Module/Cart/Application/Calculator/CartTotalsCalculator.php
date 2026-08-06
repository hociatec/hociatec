<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Calculator;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;

final class CartTotalsCalculator
{
    /**
     * @return array{items: list<array{item: CartItem, quantity: int, linePriceCents: int, rentalMonths: int|null}>, totalQuantity: int, subtotalPriceCents: int}
     */
    public function calculate(CartSession $cart): array
    {
        $items = [];
        $totalQuantity = 0;
        $subtotalPriceCents = 0;

        /** @var CartItem $item */
        foreach ($cart->getItems() as $item) {
            $quantity = $item->getQuantity();
            $linePriceCents = $item->getProduct()->getPriceCents() * $quantity;
            $rentalMonths = $item->getRentalMonths();

            if ('rental' === $item->getProduct()->getSellingType()) {
                $linePriceCents *= $rentalMonths ?? 1;
            }

            $items[] = [
                'item' => $item,
                'quantity' => $quantity,
                'linePriceCents' => $linePriceCents,
                'rentalMonths' => $rentalMonths,
            ];
            $totalQuantity += $quantity;
            $subtotalPriceCents += $linePriceCents;
        }

        return compact('items', 'totalQuantity', 'subtotalPriceCents');
    }
}
