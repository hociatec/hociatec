<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

use App\Module\Order\Application\Port\OrderInvoiceXmlRenderer;
use App\Module\Order\Domain\Entity\Order;

final class UblOrderInvoiceXmlRenderer implements OrderInvoiceXmlRenderer
{
    public function __construct(
        private readonly UblXmlFormatter $formatter = new UblXmlFormatter(),
        private readonly UblInvoiceXmlSections $sections = new UblInvoiceXmlSections(),
    ) {
    }

    public function render(Order $order, array $totals): string
    {
        $issueDate = $order->getInvoicedAt()?->format('Y-m-d') ?? (new \DateTimeImmutable())->format('Y-m-d');
        $dueDate = $order->getInvoicedAt()?->modify('+30 days')->format('Y-m-d') ?? $issueDate;
        $deliveryDate = $order->getCreatedAt()->format('Y-m-d');
        $invoiceNumber = $this->formatter->xml((string) $order->getInvoiceNumber());
        $currency = $this->formatter->xml($order->getCurrencyCode());
        $purchaseOrderNumber = $this->formatter->xml((string) ($order->getPurchaseOrderNumber() ?? $order->getNumber()));
        $paymentNote = $this->sections->payment->note();
        $supplier = $this->sections->supplier->build();
        $customer = $this->sections->customer->build($order);
        $deliveryLocation = $this->sections->delivery->build($order);
        $taxTotal = $this->sections->tax->build($totals['taxBreakdown'], (int) $totals['totalVat'], $currency);
        $monetaryTotal = $this->sections->totals->build($totals, $currency);
        $invoiceLines = $this->sections->lines->build($totals['items'], $currency);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:CustomizationID>urn:cen.eu:en16931:2017</cbc:CustomizationID>
  <cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>
  <cbc:ID>{$invoiceNumber}</cbc:ID>
  <cbc:IssueDate>{$issueDate}</cbc:IssueDate>
  <cbc:DueDate>{$dueDate}</cbc:DueDate>
  <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>
  <cbc:Note>{$paymentNote}</cbc:Note>
  <cbc:DocumentCurrencyCode>{$currency}</cbc:DocumentCurrencyCode>
  <cac:OrderReference><cbc:ID>{$purchaseOrderNumber}</cbc:ID></cac:OrderReference>
  <cac:Delivery>
    <cbc:ActualDeliveryDate>{$deliveryDate}</cbc:ActualDeliveryDate>
    {$deliveryLocation}
  </cac:Delivery>
  {$supplier}
  {$customer}
  {$taxTotal}
  {$monetaryTotal}
  {$invoiceLines}
</Invoice>
XML;
    }
}
