<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Rental\Workflow;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Shared\Application\UnitOfWork;

final readonly class RentalOrderTotalsUpdater
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function recalculateExtensionLine(OrderItem $item, int $alignedMonths): void
    {
        $quantity = max(1, $item->getQuantity());
        $grossLineTotal = $item->getUnitPriceCents() * $quantity * max(1, $alignedMonths);
        $vatRate = max(0, $item->getVatRateBps());
        $lineSubtotalHt = 0 === $vatRate
            ? $grossLineTotal
            : (int) round($grossLineTotal / (1 + ($vatRate / 10000)));
        $lineVat = max(0, $grossLineTotal - $lineSubtotalHt);

        $item->replaceLineTotals($lineSubtotalHt, $lineVat, $grossLineTotal);
    }

    public function flushItemAndOrder(OrderItem $item): void
    {
        $this->persistence->persist($item);
        if ($item->getOrder() instanceof Order) {
            $this->recalculateOrderTotals($item->getOrder());
            $this->persistence->persist($item->getOrder());
        }
        $this->persistence->flush();
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $subtotal = 0;
        foreach ($order->getItems() as $line) {
            $subtotal += $line->getLinePriceCents();
        }

        $discount = max(0, $order->getDiscountAmountCents());
        $order->replacePaymentAmounts($subtotal, $discount, max(0, $subtotal - $discount));
    }
}
