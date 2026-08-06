<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

use App\Module\Order\Application\Port\OrderInvoiceXmlRenderer;
use App\Module\Order\Domain\Entity\Order;
use App\Shared\Application\InvoiceIssuerProfile;

final class UblOrderInvoiceXmlRenderer implements OrderInvoiceXmlRenderer
{
    public function render(Order $order, array $totals): string
    {
        $issueDate = $order->getInvoicedAt()?->format('Y-m-d') ?? (new \DateTimeImmutable())->format('Y-m-d');
        $dueDate = $order->getInvoicedAt()?->modify('+30 days')->format('Y-m-d') ?? $issueDate;
        $deliveryDate = $order->getCreatedAt()->format('Y-m-d');
        $invoiceNumber = $this->xml((string) $order->getInvoiceNumber());
        $currency = $this->xml($order->getCurrencyCode());
        $customerName = $this->resolveCustomerName($order);
        $customerLegalName = $this->xml((string) ($order->getBillingCompany() ?: ('' !== $customerName ? $customerName : '-')));
        $customerEmail = $this->xml((string) ($order->getBillingEmail() ?? ''));
        $customerVat = $this->xml((string) ($order->getBillingCompanyVatNumber() ?? ''));
        $purchaseOrderNumber = $this->xml((string) ($order->getPurchaseOrderNumber() ?? $order->getNumber()));
        $issuerVat = $this->xml(InvoiceIssuerProfile::VAT);
        $issuerEmail = $this->xml(InvoiceIssuerProfile::EMAIL);
        $issuerName = $this->xml(InvoiceIssuerProfile::NAME);
        $issuerSiret = $this->xml(str_replace(' ', '', InvoiceIssuerProfile::SIRET));
        $issuerAddress = array_map($this->xml(...), InvoiceIssuerProfile::ADDRESS_LINES);
        $paymentNote = $this->xml(implode(' ', [
            InvoiceIssuerProfile::PAYMENT_TERMS,
            InvoiceIssuerProfile::EARLY_PAYMENT_DISCOUNT,
            InvoiceIssuerProfile::LATE_PENALTY,
            InvoiceIssuerProfile::RECOVERY_FEE,
        ]));

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
    {$this->deliveryLocationXml($order)}
  </cac:Delivery>
  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PartyName><cbc:Name>{$issuerName}</cbc:Name></cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>{$issuerAddress[0]}</cbc:StreetName>
        <cbc:CityName>{$issuerAddress[1]}</cbc:CityName>
        <cbc:PostalZone>{$issuerAddress[2]}</cbc:PostalZone>
        <cac:Country><cbc:IdentificationCode>FR</cbc:IdentificationCode></cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme><cbc:CompanyID>{$issuerVat}</cbc:CompanyID><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:PartyTaxScheme>
      <cac:PartyLegalEntity><cbc:RegistrationName>{$issuerName}</cbc:RegistrationName><cbc:CompanyID>{$issuerSiret}</cbc:CompanyID></cac:PartyLegalEntity>
      <cac:Contact><cbc:ElectronicMail>{$issuerEmail}</cbc:ElectronicMail></cac:Contact>
    </cac:Party>
  </cac:AccountingSupplierParty>
  <cac:AccountingCustomerParty>
    <cac:Party>
      <cac:PartyName><cbc:Name>{$this->xml('' !== $customerName ? $customerName : '-')}</cbc:Name></cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>{$this->xml((string) ($order->getBillingAddress() ?? '-'))}</cbc:StreetName>
        <cbc:CityName>{$this->xml((string) ($order->getBillingCity() ?? '-'))}</cbc:CityName>
        <cbc:PostalZone>{$this->xml((string) ($order->getBillingPostalCode() ?? '-'))}</cbc:PostalZone>
        <cac:Country><cbc:IdentificationCode>FR</cbc:IdentificationCode></cac:Country>
      </cac:PostalAddress>
      {$this->customerTaxSchemeXml($order, $customerVat)}
      <cac:PartyLegalEntity><cbc:RegistrationName>{$customerLegalName}</cbc:RegistrationName></cac:PartyLegalEntity>
      <cac:Contact><cbc:ElectronicMail>{$customerEmail}</cbc:ElectronicMail></cac:Contact>
    </cac:Party>
  </cac:AccountingCustomerParty>
  <cac:TaxTotal>
    <cbc:TaxAmount currencyID="{$currency}">{$this->amount((int) $totals['totalVat'])}</cbc:TaxAmount>
    {$this->taxSubtotalXml($totals['taxBreakdown'], $currency)}
  </cac:TaxTotal>
  <cac:LegalMonetaryTotal>
    <cbc:LineExtensionAmount currencyID="{$currency}">{$this->amount((int) $totals['totalHt'])}</cbc:LineExtensionAmount>
    <cbc:TaxExclusiveAmount currencyID="{$currency}">{$this->amount((int) $totals['totalHt'])}</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="{$currency}">{$this->amount((int) $totals['totalTtc'])}</cbc:TaxInclusiveAmount>
    <cbc:AllowanceTotalAmount currencyID="{$currency}">{$this->amount((int) $totals['totalDiscountTtc'])}</cbc:AllowanceTotalAmount>
    <cbc:PayableAmount currencyID="{$currency}">{$this->amount((int) $totals['totalTtc'])}</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>
  {$this->invoiceLinesXml($totals['items'], $currency)}
</Invoice>
XML;
    }

    private function deliveryLocationXml(Order $order): string
    {
        if (null === $order->getShippingAddress() || trim((string) $order->getShippingAddress()) === trim((string) $order->getBillingAddress())) {
            return '';
        }

        return sprintf(
            '<cac:DeliveryLocation><cac:Address><cbc:StreetName>%s</cbc:StreetName><cbc:CityName>%s</cbc:CityName><cbc:PostalZone>%s</cbc:PostalZone><cac:Country><cbc:IdentificationCode>FR</cbc:IdentificationCode></cac:Country></cac:Address></cac:DeliveryLocation>',
            $this->xml((string) $order->getShippingAddress()),
            $this->xml((string) ($order->getShippingCity() ?? '-')),
            $this->xml((string) ($order->getShippingPostalCode() ?? '-')),
        );
    }

    private function customerTaxSchemeXml(Order $order, string $customerVat): string
    {
        if (null === $order->getBillingCompanyVatNumber() || '' === trim($order->getBillingCompanyVatNumber())) {
            return '';
        }

        return sprintf('<cac:PartyTaxScheme><cbc:CompanyID>%s</cbc:CompanyID><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:PartyTaxScheme>', $customerVat);
    }

    /**
     * @param list<array{rateBps:int, taxableCents:int, taxCents:int}> $taxBreakdown
     */
    private function taxSubtotalXml(array $taxBreakdown, string $currency): string
    {
        $xml = '';
        foreach ($taxBreakdown as $taxLine) {
            $xml .= sprintf(
                '<cac:TaxSubtotal><cbc:TaxableAmount currencyID="%s">%s</cbc:TaxableAmount><cbc:TaxAmount currencyID="%s">%s</cbc:TaxAmount><cac:TaxCategory><cbc:Percent>%s</cbc:Percent><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:TaxCategory></cac:TaxSubtotal>',
                $currency,
                $this->amount((int) $taxLine['taxableCents']),
                $currency,
                $this->amount((int) $taxLine['taxCents']),
                $this->percent((int) $taxLine['rateBps']),
            );
        }

        return $xml;
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    private function invoiceLinesXml(array $items, string $currency): string
    {
        $xml = '';
        $lineId = 1;
        foreach ($items as $item) {
            $xml .= sprintf(
                '<cac:InvoiceLine><cbc:ID>%d</cbc:ID><cbc:InvoicedQuantity unitCode="EA">%d</cbc:InvoicedQuantity><cbc:LineExtensionAmount currencyID="%s">%s</cbc:LineExtensionAmount><cac:Item><cbc:Description>%s</cbc:Description><cbc:Name>%s</cbc:Name><cac:SellersItemIdentification><cbc:ID>%s</cbc:ID></cac:SellersItemIdentification><cac:ClassifiedTaxCategory><cbc:Percent>%s</cbc:Percent><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:ClassifiedTaxCategory></cac:Item><cac:Price><cbc:PriceAmount currencyID="%s">%s</cbc:PriceAmount></cac:Price></cac:InvoiceLine>',
                $lineId++,
                (int) $item['quantity'],
                $currency,
                $this->amount((int) $item['lineSubtotalHtCents']),
                $this->xml(InvoiceIssuerProfile::OPERATION_NATURE),
                $this->xml((string) $item['name']),
                $this->xml((string) $item['sku']),
                $this->percent((int) $item['vatRateBps']),
                $currency,
                $this->amount((int) $item['unitPriceHtCents']),
            );
        }

        return $xml;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function percent(int $rateBps): string
    {
        return number_format($rateBps / 100, 2, '.', '');
    }

    private function resolveCustomerName(Order $order): string
    {
        $billingName = trim((string) $order->getBillingName());
        if ('' !== $billingName) {
            return $billingName;
        }

        return trim($order->getUser()->getFirstName().' '.$order->getUser()->getLastName());
    }
}
