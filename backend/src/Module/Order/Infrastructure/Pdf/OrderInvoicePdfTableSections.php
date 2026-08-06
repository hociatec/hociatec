<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final readonly class OrderInvoicePdfTableSections
{
    public function __construct(private PdfHtmlFormatter $formatter)
    {
    }

    /** @param list<array<string,mixed>> $items */
    public function items(array $items): string
    {
        $rows = (new OrderInvoicePdfRows($this->formatter))->items($items);

        return <<<HTML
<section>
  <table>
    <caption>Lignes de facture</caption>
    <thead>
      <tr>
        <th scope="col">Produit</th><th scope="col">SKU</th><th scope="col" class="num">Qté</th><th scope="col" class="num">PU HT</th>
        <th scope="col" class="num">TVA</th><th scope="col" class="num">Total HT</th><th scope="col" class="num">Montant TVA</th><th scope="col" class="num">Total TTC</th>
      </tr>
    </thead>
    <tbody>{$rows}</tbody>
  </table>
</section>
HTML;
    }

    /** @param list<array{rateBps:int, taxableCents:int, taxCents:int}> $taxBreakdown */
    public function taxes(array $taxBreakdown): string
    {
        $taxRows = (new OrderInvoicePdfRows($this->formatter))->taxes($taxBreakdown);

        return <<<HTML
<section>
  <table>
    <caption>Ventilation TVA</caption>
    <thead><tr><th scope="col">Taux</th><th scope="col" class="num">Base HT</th><th scope="col" class="num">Montant TVA</th></tr></thead>
    <tbody>{$taxRows}</tbody>
  </table>
</section>
HTML;
    }

    /** @param array{subtotalTtcBeforeDiscount:int,totalDiscountTtc:int,totalHt:int,totalVat:int,totalTtc:int} $totals */
    public function totals(array $totals): string
    {
        return <<<HTML
<section>
  <table>
    <caption>Totaux</caption>
    <tbody>
      <tr><th scope="row">Sous-total TTC avant remise</th><td class="num">{$this->formatter->money((int) $totals['subtotalTtcBeforeDiscount'])}</td></tr>
      <tr><th scope="row">Remise TTC</th><td class="num">- {$this->formatter->money((int) $totals['totalDiscountTtc'])}</td></tr>
      <tr><th scope="row">Total HT</th><td class="num">{$this->formatter->money((int) $totals['totalHt'])}</td></tr>
      <tr><th scope="row">Total TVA</th><td class="num">{$this->formatter->money((int) $totals['totalVat'])}</td></tr>
      <tr><th scope="row">Total TTC</th><td class="num"><strong>{$this->formatter->money((int) $totals['totalTtc'])}</strong></td></tr>
    </tbody>
  </table>
</section>
HTML;
    }
}
