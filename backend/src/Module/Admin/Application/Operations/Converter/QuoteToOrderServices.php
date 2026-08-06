<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Converter;

use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Application\Factory\InvoiceNumberGenerator;
use App\Module\Order\Application\Factory\OrderNumberGenerator;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Application\Workflow\OrderNotificationEmailService;

final readonly class QuoteToOrderServices
{
    public function __construct(
        public OrderNumberGenerator $orderNumbers,
        public InvoiceNumberGenerator $invoiceNumbers,
        public OrderInvoiceCalculator $invoiceCalculator,
        public OrderNotificationEmailService $notifications,
        public OrderEventLogger $events,
    ) {
    }
}
