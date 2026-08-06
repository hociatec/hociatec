<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

use App\Module\Order\Application\DTO\OrderCustomerSnapshot;
use App\Module\Order\Domain\Entity\Order;

final readonly class UblInvoiceCustomerXml
{
    public function __construct(private UblXmlFormatter $formatter = new UblXmlFormatter())
    {
    }

    public function build(Order $order): string
    {
        $customerName = $this->resolveCustomerName($order);
        $customerLegalName = $this->formatter->xml((string) ($order->getBillingCompany() ?: ('' !== $customerName ? $customerName : '-')));
        $customerEmail = $this->formatter->xml((string) ($order->getBillingEmail() ?? ''));
        $customerVat = $this->formatter->xml((string) ($order->getBillingCompanyVatNumber() ?? ''));

        return <<<XML
<cac:AccountingCustomerParty>
    <cac:Party>
      <cac:PartyName><cbc:Name>{$this->formatter->xml('' !== $customerName ? $customerName : '-')}</cbc:Name></cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>{$this->formatter->xml((string) ($order->getBillingAddress() ?? '-'))}</cbc:StreetName>
        <cbc:CityName>{$this->formatter->xml((string) ($order->getBillingCity() ?? '-'))}</cbc:CityName>
        <cbc:PostalZone>{$this->formatter->xml((string) ($order->getBillingPostalCode() ?? '-'))}</cbc:PostalZone>
        <cac:Country><cbc:IdentificationCode>FR</cbc:IdentificationCode></cac:Country>
      </cac:PostalAddress>
      {$this->taxScheme($order, $customerVat)}
      <cac:PartyLegalEntity><cbc:RegistrationName>{$customerLegalName}</cbc:RegistrationName></cac:PartyLegalEntity>
      <cac:Contact><cbc:ElectronicMail>{$customerEmail}</cbc:ElectronicMail></cac:Contact>
    </cac:Party>
  </cac:AccountingCustomerParty>
XML;
    }

    private function taxScheme(Order $order, string $customerVat): string
    {
        if (null === $order->getBillingCompanyVatNumber() || '' === trim($order->getBillingCompanyVatNumber())) {
            return '';
        }

        return sprintf('<cac:PartyTaxScheme><cbc:CompanyID>%s</cbc:CompanyID><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:PartyTaxScheme>', $customerVat);
    }

    private function resolveCustomerName(Order $order): string
    {
        $billingName = trim((string) $order->getBillingName());
        if ('' !== $billingName) {
            return $billingName;
        }

        return OrderCustomerSnapshot::fromOrder($order)->displayName();
    }
}
