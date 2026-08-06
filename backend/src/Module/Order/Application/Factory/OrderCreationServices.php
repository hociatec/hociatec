<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Domain\Entity\Order;

final readonly class OrderCreationServices
{
    public function __construct(
        private OrderNumberGenerator $orderNumbers,
        private InvoiceNumberGenerator $invoiceNumbers,
        private OrderInvoiceCalculator $invoiceCalculator,
    ) {
    }

    public function nextOrderNumber(): string
    {
        return $this->orderNumbers->generate();
    }

    public function nextInvoiceNumber(): string
    {
        return $this->invoiceNumbers->generate();
    }

    public function snapshotInvoice(Order $order): void
    {
        $this->invoiceCalculator->snapshot($order);
    }
}
