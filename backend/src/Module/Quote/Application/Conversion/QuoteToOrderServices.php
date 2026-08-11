<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Conversion;

use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Application\Factory\InvoiceNumberGenerator;
use App\Module\Order\Application\Factory\OrderNumberGenerator;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Application\Workflow\OrderNotificationEmailService;
use App\Module\Order\Domain\Entity\Order;

final readonly class QuoteToOrderServices
{
    public function __construct(
        private OrderNumberGenerator $orderNumbers,
        private InvoiceNumberGenerator $invoiceNumbers,
        private OrderInvoiceCalculator $invoiceCalculator,
        private OrderNotificationEmailService $notifications,
        private OrderEventLogger $events,
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

    public function sendOrderCreatedNotification(Order $order): bool
    {
        return $this->notifications->sendOrderCreatedIfNeeded($order);
    }

    public function logEmailFailure(Order $order, \RuntimeException $exception): void
    {
        $this->events->log($order, null, 'email_failed', 'Échec email commande à régler: '.$exception->getMessage());
    }
}
