<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

final readonly class UblInvoiceXmlSections
{
    public function __construct(
        public UblInvoiceSupplierXml $supplier = new UblInvoiceSupplierXml(),
        public UblInvoiceCustomerXml $customer = new UblInvoiceCustomerXml(),
        public UblInvoiceDeliveryXml $delivery = new UblInvoiceDeliveryXml(),
        public UblInvoiceTaxXml $tax = new UblInvoiceTaxXml(),
        public UblInvoiceTotalsXml $totals = new UblInvoiceTotalsXml(),
        public UblInvoiceLinesXml $lines = new UblInvoiceLinesXml(),
        public UblInvoicePaymentXml $payment = new UblInvoicePaymentXml(),
    ) {
    }
}
