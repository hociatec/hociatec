<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\Order;

interface OrderInvoiceXmlRenderer
{
    /**
     * @param array{
     *   subtotalTtcBeforeDiscount:int,
     *   totalDiscountTtc:int,
     *   totalHt:int,
     *   totalVat:int,
     *   totalTtc:int,
     *   taxBreakdown:list<array{rateBps:int, taxableCents:int, taxCents:int}>,
     *   items:list<array<string,mixed>>
     * } $totals
     */
    public function render(Order $order, array $totals): string;
}
