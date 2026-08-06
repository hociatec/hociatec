<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

use App\Shared\Application\InvoiceIssuerProfile;

final readonly class UblInvoiceSupplierXml
{
    public function __construct(private UblXmlFormatter $formatter = new UblXmlFormatter())
    {
    }

    public function build(): string
    {
        $issuerVat = $this->formatter->xml(InvoiceIssuerProfile::VAT);
        $issuerEmail = $this->formatter->xml(InvoiceIssuerProfile::EMAIL);
        $issuerName = $this->formatter->xml(InvoiceIssuerProfile::NAME);
        $issuerSiret = $this->formatter->xml(str_replace(' ', '', InvoiceIssuerProfile::SIRET));
        $issuerAddress = array_map($this->formatter->xml(...), InvoiceIssuerProfile::ADDRESS_LINES);

        return <<<XML
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
XML;
    }
}
