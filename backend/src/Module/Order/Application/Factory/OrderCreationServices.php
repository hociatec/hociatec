<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;

final readonly class OrderCreationServices
{
    public function __construct(
        public OrderNumberGenerator $orderNumbers,
        public InvoiceNumberGenerator $invoiceNumbers,
        public OrderInvoiceCalculator $invoiceCalculator,
    ) {
    }
}
