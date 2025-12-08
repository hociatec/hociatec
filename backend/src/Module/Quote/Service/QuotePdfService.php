<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;

class QuotePdfService
{
    public function render(Quote $quote, array $totals): string
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new \RuntimeException('Dompdf non installe');
        }

        $html = $this->buildHtml($quote, $totals);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildHtml(Quote $quote, array $totals): string
    {
        $itemsRows = '';
        foreach ($quote->getItems() as $item) {
            $qty = $item->getQuantity();
            $unit = $item->getUnit() ?? '';
            $unitPrice = number_format($item->getUnitPriceCents() / 100, 2, ',', ' ');
            $discount = number_format(($item->getDiscountCents() ?? 0) / 100, 2, ',', ' ');
            $calc = (new QuoteCalculator())->computeItemTotals($item);
            $lineTtc = number_format($calc['ttc'] / 100, 2, ',', ' ');
            $itemsRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td class="num">%d</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s</td></tr>',
                htmlspecialchars($item->getName()),
                htmlspecialchars($item->getDescription() ?? ''),
                $qty,
                $unitPrice,
                $discount,
                $lineTtc,
            );
        }

        $totalHt = number_format($totals['totalHt'] / 100, 2, ',', ' ');
        $totalVat = number_format($totals['totalVat'] / 100, 2, ',', ' ');
        $totalTtc = number_format($totals['totalTtc'] / 100, 2, ',', ' ');

        $statusLabel = htmlspecialchars(QuoteStatusTranslator::toLabel($quote->getStatus()));
        $customer = sprintf(
            '<div><strong>%s</strong><br/>%s<br/>%s</div>',
            htmlspecialchars($quote->getCustomerName() ?? ''),
            htmlspecialchars($quote->getCustomerCompany() ?? ''),
            nl2br(htmlspecialchars($quote->getCustomerAddress() ?? '')),
        );

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 20px; margin: 0 0 6px; }
    .header { display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .muted { color: #555; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
    .num { text-align: right; white-space: nowrap; }
    .totals { margin-top: 12px; width: 40%; margin-left: auto; }
    .totals td { padding: 6px; }
  </style>
  <title>{$quote->getNumber()}</title>
  </head>
  <body>
    <div class="header">
      <div>
        <h1>Devis {$quote->getNumber()}</h1>
        <div class="muted">Statut: {$statusLabel}</div>
        <div class="muted">Date: {$quote->getCreatedAt()->format('d/m/Y')}</div>
      </div>
      <div>
        {$customer}
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>Description</th>
          <th>Qté</th>
          <th>Prix HT</th>
          <th>Remise</th>
          <th>Total TTC</th>
        </tr>
      </thead>
      <tbody>
        {$itemsRows}
      </tbody>
    </table>

    <table class="totals">
      <tr><td>Total HT</td><td class="num"><strong>{$totalHt} €</strong></td></tr>
      <tr><td>TVA</td><td class="num"><strong>{$totalVat} €</strong></td></tr>
      <tr><td>TTC</td><td class="num"><strong>{$totalTtc} €</strong></td></tr>
    </table>

    <div style="margin-top:16px" class="muted">{$quote->getConditions()}</div>
  </body>
</html>
HTML;
    }
}
