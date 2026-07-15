<?php

declare(strict_types=1);

namespace App\Module\Cart\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Promotion\Service\PromotionEngine;
use App\Module\User\Entity\User;

final class CartFormatter
{
    public function __construct(private readonly PromotionEngine $promotionEngine)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCart(CartSession $cart, ?User $user = null): array
    {
        $items = [];
        $totalQuantity = 0;

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
        }

        $summary = $this->promotionEngine->calculateCartSummary($cart, $user);

        return [
            'token' => $cart->getToken(),
            'items' => $items,
            'totalQuantity' => $totalQuantity,
            'subtotalPriceCents' => $summary['subtotalPriceCents'],
            'discountAmountCents' => $summary['discountAmountCents'],
            'totalPriceCents' => $summary['totalPriceCents'],
            'appliedPromotion' => $summary['appliedPromotion'],
            'eligiblePromotions' => $summary['eligiblePromotions'],
            'updatedAt' => $cart->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
