<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

final readonly class UblInvoiceTaxXml
{
    public function __construct(private UblXmlFormatter $formatter = new UblXmlFormatter())
    {
    }

    /**
     * @param list<array{rateBps:int, taxableCents:int, taxCents:int}> $taxBreakdown
     */
    public function build(array $taxBreakdown, int $totalVat, string $currency): string
    {
        return sprintf(
            '<cac:TaxTotal><cbc:TaxAmount currencyID="%s">%s</cbc:TaxAmount>%s</cac:TaxTotal>',
            $currency,
            $this->formatter->amount($totalVat),
            $this->subtotals($taxBreakdown, $currency),
        );
    }

    /** @param list<array{rateBps:int, taxableCents:int, taxCents:int}> $taxBreakdown */
    private function subtotals(array $taxBreakdown, string $currency): string
    {
        $xml = '';
        foreach ($taxBreakdown as $taxLine) {
            $xml .= sprintf(
                '<cac:TaxSubtotal><cbc:TaxableAmount currencyID="%s">%s</cbc:TaxableAmount><cbc:TaxAmount currencyID="%s">%s</cbc:TaxAmount><cac:TaxCategory><cbc:Percent>%s</cbc:Percent><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:TaxCategory></cac:TaxSubtotal>',
                $currency,
                $this->formatter->amount((int) $taxLine['taxableCents']),
                $currency,
                $this->formatter->amount((int) $taxLine['taxCents']),
                $this->formatter->percent((int) $taxLine['rateBps']),
            );
        }

        return $xml;
    }
}
