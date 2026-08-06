<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

use App\Shared\Application\InvoiceIssuerProfile;

final readonly class UblInvoiceLinesXml
{
    public function __construct(private UblXmlFormatter $formatter = new UblXmlFormatter())
    {
    }

    /** @param list<array<string,mixed>> $items */
    public function build(array $items, string $currency): string
    {
        $xml = '';
        $lineId = 1;
        foreach ($items as $item) {
            $xml .= sprintf(
                '<cac:InvoiceLine><cbc:ID>%d</cbc:ID><cbc:InvoicedQuantity unitCode="EA">%d</cbc:InvoicedQuantity><cbc:LineExtensionAmount currencyID="%s">%s</cbc:LineExtensionAmount><cac:Item><cbc:Description>%s</cbc:Description><cbc:Name>%s</cbc:Name><cac:SellersItemIdentification><cbc:ID>%s</cbc:ID></cac:SellersItemIdentification><cac:ClassifiedTaxCategory><cbc:Percent>%s</cbc:Percent><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:ClassifiedTaxCategory></cac:Item><cac:Price><cbc:PriceAmount currencyID="%s">%s</cbc:PriceAmount></cac:Price></cac:InvoiceLine>',
                $lineId++,
                (int) $item['quantity'],
                $currency,
                $this->formatter->amount((int) $item['lineSubtotalHtCents']),
                $this->formatter->xml(InvoiceIssuerProfile::OPERATION_NATURE),
                $this->formatter->xml((string) $item['name']),
                $this->formatter->xml((string) $item['sku']),
                $this->formatter->percent((int) $item['vatRateBps']),
                $currency,
                $this->formatter->amount((int) $item['unitPriceHtCents']),
            );
        }

        return $xml;
    }
}
