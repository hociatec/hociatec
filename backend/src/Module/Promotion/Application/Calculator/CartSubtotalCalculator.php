<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Calculator;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;

final readonly class CartSubtotalCalculator
{
    public function calculate(CartSession $cart): int
    {
        $subtotal = 0;

        /** @var CartItem $item */
        foreach ($cart->getItems() as $item) {
            $linePrice = $item->getProduct()->getUnitPriceCentsForSellingType($item->getSellingType()) * $item->getQuantity();
            if ('rental' === $item->getSellingType()) {
                $linePrice *= max(1, $item->getRentalMonths() ?? 1);
            }

            $subtotal += $linePrice;
        }

        return $subtotal;
    }
}
