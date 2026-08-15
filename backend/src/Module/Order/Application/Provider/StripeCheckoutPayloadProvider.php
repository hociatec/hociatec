<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Provider;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Calculator\VoucherEngine;

final readonly class StripeCheckoutPayloadProvider
{
    public function __construct(
        private PromotionEngine $promotionEngine,
        private VoucherEngine $voucherEngine,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function cartItems(CartSession $cart): array
    {
        $items = [];
        /** @var CartItem $item */
        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();
            $rentalMonths = $item->getRentalMonths();
            $rentalStartDate = $item->getRentalStartDateString();
            $rentalEndDate = $item->getRentalEndDateString();
            $unitPrice = $product->getPriceCents();
            $label = $product->getName();
            if ('rental' === $product->getSellingType() && null !== $rentalMonths) {
                $unitPrice *= max(1, $rentalMonths);
                $label .= sprintf(' (%d mois, du %s au %s)', $rentalMonths, $rentalStartDate ?? '-', $rentalEndDate ?? '-');
            }

            $items[] = [
                'productId' => $product->getId(),
                'productName' => $label,
                'productSku' => $product->getSku(),
                'unitPriceCents' => $unitPrice,
                'quantity' => $item->getQuantity(),
                'vatRateBps' => 2000,
                'rentalMonths' => $rentalMonths,
                'rentalStartDate' => $rentalStartDate,
                'rentalEndDate' => $rentalEndDate,
                'sellingType' => $product->getSellingType(),
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    public function orderItems(Order $order): array
    {
        $items = [];
        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $items[] = [
                'productId' => $item->getProduct()?->getId(),
                'productName' => $item->getProductName(),
                'productSku' => $item->getProductSku(),
                'unitPriceCents' => $item->getUnitPriceCents(),
                'quantity' => $item->getQuantity(),
                'vatRateBps' => $item->getVatRateBps(),
            ];
        }

        return $items;
    }

    /** @return array<string, mixed> */
    public function cartSummary(CartSession $cart, User $user): array
    {
        $promotion = $this->promotionEngine->calculateCartSummary($cart, $user);
        $voucher = $this->voucherEngine->calculateCartSummary($cart, $user, $cart->getVoucherCode());

        return null !== $cart->getVoucherCode() && 'applied' === $voucher['voucherCodeStatus']
            ? $voucher
            : $promotion;
    }
}
