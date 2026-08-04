<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Calculator;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;

final class OrderInvoiceCalculator
{
    /**
     * Freeze invoice line amounts on the order items.
     */
    public function snapshot(Order $order): void
    {
        $items = $order->getItems()->toArray();
        if ([] === $items) {
            return;
        }

        $grossSubtotal = 0;
        foreach ($items as $item) {
            $grossSubtotal += $item->getUnitPriceCents() * max(1, $item->getQuantity());
        }

        $discountRemaining = max(0, $order->getDiscountAmountCents());
        $lastIndex = count($items) - 1;

        foreach ($items as $index => $item) {
            $grossLineTotal = $item->getUnitPriceCents() * max(1, $item->getQuantity());
            $lineDiscount = 0;

            if ($discountRemaining > 0 && $grossSubtotal > 0) {
                $lineDiscount = $index === $lastIndex
                    ? min($discountRemaining, $grossLineTotal)
                    : min(
                        $discountRemaining,
                        (int) round($order->getDiscountAmountCents() * ($grossLineTotal / $grossSubtotal))
                    );
            }

            $discountRemaining = max(0, $discountRemaining - $lineDiscount);
            $lineTotalTtc = max(0, $grossLineTotal - $lineDiscount);
            $vatRate = max(0, $item->getVatRateBps());
            $lineSubtotalHt = 0 === $vatRate
                ? $lineTotalTtc
                : (int) round($lineTotalTtc / (1 + ($vatRate / 10000)));
            $lineVat = max(0, $lineTotalTtc - $lineSubtotalHt);

            $item
                ->setLineSubtotalCents($lineSubtotalHt)
                ->setLineVatCents($lineVat)
                ->setLineTotalCents($lineTotalTtc);
        }
    }

    /**
     * @return array{
     *   subtotalTtcBeforeDiscount:int,
     *   totalDiscountTtc:int,
     *   totalHt:int,
     *   totalVat:int,
     *   totalTtc:int,
     *   taxBreakdown:list<array{rateBps:int, taxableCents:int, taxCents:int}>,
     *   items:list<array<string,mixed>>
     * }
     */
    public function computeTotals(Order $order): array
    {
        $subtotalTtcBeforeDiscount = 0;
        $totalHt = 0;
        $totalVat = 0;
        $totalTtc = 0;
        $taxBreakdown = [];
        $items = [];

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $rawLineTotal = $item->getUnitPriceCents() * max(1, $item->getQuantity());
            $lineHt = $item->getLineSubtotalCents();
            $lineVat = $item->getLineVatCents();
            $lineTtc = $item->getLineTotalCents() > 0 ? $item->getLineTotalCents() : $rawLineTotal;
            $vatRateBps = $item->getVatRateBps();
            $quantity = max(1, $item->getQuantity());
            $unitPriceHt = (int) round($lineHt / $quantity);

            $subtotalTtcBeforeDiscount += $rawLineTotal;
            $totalHt += $lineHt;
            $totalVat += $lineVat;
            $totalTtc += $lineTtc;

            if (!isset($taxBreakdown[$vatRateBps])) {
                $taxBreakdown[$vatRateBps] = [
                    'rateBps' => $vatRateBps,
                    'taxableCents' => 0,
                    'taxCents' => 0,
                ];
            }
            $taxBreakdown[$vatRateBps]['taxableCents'] += $lineHt;
            $taxBreakdown[$vatRateBps]['taxCents'] += $lineVat;

            $items[] = [
                'name' => $item->getProductName(),
                'sku' => $item->getProductSku(),
                'quantity' => $quantity,
                'unitPriceHtCents' => $unitPriceHt,
                'unitPriceTtcCents' => $item->getUnitPriceCents(),
                'vatRateBps' => $vatRateBps,
                'lineSubtotalHtCents' => $lineHt,
                'lineVatCents' => $lineVat,
                'lineTotalTtcCents' => $lineTtc,
            ];
        }

        return [
            'subtotalTtcBeforeDiscount' => $subtotalTtcBeforeDiscount,
            'totalDiscountTtc' => max(0, $order->getDiscountAmountCents()),
            'totalHt' => $totalHt,
            'totalVat' => $totalVat,
            'totalTtc' => $totalTtc,
            'taxBreakdown' => array_values($taxBreakdown),
            'items' => $items,
        ];
    }
}
