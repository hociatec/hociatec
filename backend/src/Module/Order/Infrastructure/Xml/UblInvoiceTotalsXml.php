<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

final readonly class UblInvoiceTotalsXml
{
    public function __construct(private UblXmlFormatter $formatter = new UblXmlFormatter())
    {
    }

    /** @param array{totalHt:int,totalTtc:int,totalDiscountTtc:int} $totals */
    public function build(array $totals, string $currency): string
    {
        return <<<XML
<cac:LegalMonetaryTotal>
    <cbc:LineExtensionAmount currencyID="{$currency}">{$this->formatter->amount((int) $totals['totalHt'])}</cbc:LineExtensionAmount>
    <cbc:TaxExclusiveAmount currencyID="{$currency}">{$this->formatter->amount((int) $totals['totalHt'])}</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="{$currency}">{$this->formatter->amount((int) $totals['totalTtc'])}</cbc:TaxInclusiveAmount>
    <cbc:AllowanceTotalAmount currencyID="{$currency}">{$this->formatter->amount((int) $totals['totalDiscountTtc'])}</cbc:AllowanceTotalAmount>
    <cbc:PayableAmount currencyID="{$currency}">{$this->formatter->amount((int) $totals['totalTtc'])}</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>
XML;
    }
}
