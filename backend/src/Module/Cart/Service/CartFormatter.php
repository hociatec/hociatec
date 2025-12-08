<?php

declare(strict_types=1);

namespace App\Module\Cart\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Service\CatalogFormatter;

final class CartFormatter
{
    private function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatCart(CartSession $cart): array
    {
        $items = [];
        $totalQuantity = 0;
        $totalPriceCents = 0;

        /** @var CartItem $item */
        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();
            $linePrice = $product->getPriceCents() * $quantity;
            $rentalMonths = $item->getRentalMonths();

            if ($product->getSellingType() === 'rental') {
                $months = $rentalMonths ?? 1;
                $linePrice *= $months;
            }

            $items[] = [
                'id' => $item->getId(),
                'product' => CatalogFormatter::formatProduct($product),
                'quantity' => $quantity,
                'linePriceCents' => $linePrice,
                'rentalMonths' => $rentalMonths,
            ];

            $totalQuantity += $quantity;
            $totalPriceCents += $linePrice;
        }

        return [
            'token' => $cart->getToken(),
            'items' => $items,
            'totalQuantity' => $totalQuantity,
            'totalPriceCents' => $totalPriceCents,
            'updatedAt' => $cart->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
