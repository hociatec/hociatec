<?php

declare(strict_types=1);

namespace App\Module\Quote\Infrastructure\Pdf;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Mapper\QuoteStatusTranslator;
use App\Module\Quote\Application\Workflow\QuoteService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final class QuotePdfTemplateRenderer
{
    private const ISSUER_EMAIL = 'contact@hociatec.fr';
    private const ISSUER_ADDRESS_LINES = [
        '2 allée Anatoli Vaisser',
        '92600 Asnières-sur-Seine',
        'France',
    ];
    private const ISSUER_SIREN = '934 814 559';
    private const ISSUER_SIRET = '934 814 559 00019';

    public function __construct(private readonly PdfHtmlFormatter $formatter)
    {
    }

    /** @param array{totalHt: int, totalVat: int, totalTtc: int} $totals */
    public function buildHtml(Quote $quote, array $totals): string
    {
        $quoteNumber = $this->formatter->escape($quote->getNumber());
        $statusLabel = $this->formatter->escape(QuoteStatusTranslator::toLabel($quote->getStatus()));
        $issuedAt = $this->formatter->date($quote->getCreatedAt()->format('Y-m-d'));
        $validFrom = $this->formatter->date($quote->getValidFrom()?->format('Y-m-d'));
        $validUntil = $this->formatter->date($quote->getValidUntil()?->format('Y-m-d'));
        $conditions = $this->formatConditions($quote->getConditions() ?: QuoteService::DEFAULT_CONDITIONS);
        $css = QuotePdfStyles::documentCss();

        $issuerLines = implode('', array_map(
            fn (string $line): string => '<p>'.$this->formatter->escape($line).'</p>',
            self::ISSUER_ADDRESS_LINES,
        ));
        $customerName = $quote->getCustomerName() ? $this->formatter->escape($quote->getCustomerName()) : '-';
        $customerCompany = $quote->getCustomerCompany() ? '<p>'.$this->formatter->escape($quote->getCustomerCompany()).'</p>' : '';
        $customerAddress = $quote->getCustomerAddress() ? $this->formatMultilineAddress($quote->getCustomerAddress()) : '';
        $customerEmail = $quote->getCustomerEmail() ? '<p>Email : '.$this->formatter->escape($quote->getCustomerEmail()).'</p>' : '';

        $rows = '';
        foreach ($quote->getItems() as $item) {
            $calculated = (new QuoteCalculator())->computeItemTotals($item);
            $quantity = $item->getQuantity();
            $unit = trim((string) $item->getUnit());
            $quantityLabel = $quantity.('' !== $unit ? ' '.$unit : '');
            $description = $item->getDescription() ? nl2br($this->formatter->escape($item->getDescription())) : '—';

            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td class="num">%s</td><td class="num">%s %%</td><td class="num">%s</td><td class="num">%s</td></tr>',
                $this->formatter->escape($item->getName()),
                $description,
                $this->formatter->escape($quantityLabel),
                $this->formatter->money($item->getUnitPriceCents()),
                number_format($item->getVatRateBps() / 100, 2, ',', ' '),
                $this->formatter->money($item->getDiscountCents()),
                $this->formatter->money($calculated['ht']),
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="author" content="Hociatec">
  <meta name="description" content="Devis commercial émis par Hociatec">
  <meta name="generator" content="WeasyPrint PDF/UA">
  <title>Devis {$quoteNumber}</title>
  <style>
    {$css}
  </style>
</head>
<body>
  <main>
    <header aria-labelledby="quote-title">
      <h1 id="quote-title">Devis {$quoteNumber}</h1>
      <p class="lead">Document commercial émis par Hociatec.</p>
      <p><strong>Statut :</strong> <span class="status">{$statusLabel}</span></p>
    </header>

    <section class="section-card" aria-labelledby="dates-title">
      <h2 id="dates-title">Dates et validité</h2>
      <dl class="meta-list">
        <dt>Date d'émission</dt>
        <dd>{$issuedAt}</dd>
        <dt>Début de validité</dt>
        <dd>{$validFrom}</dd>
        <dt>Fin de validité</dt>
        <dd>{$validUntil}</dd>
      </dl>
    </section>

    <section class="section-card" aria-labelledby="issuer-title">
      <h2 id="issuer-title">Émetteur</h2>
      <address>
        <p><strong>Hociatec</strong></p>
        {$issuerLines}
        <p>Email : {$this->formatter->escape(self::ISSUER_EMAIL)}</p>
        <p>SIREN : {$this->formatter->escape(self::ISSUER_SIREN)}</p>
        <p>SIRET : {$this->formatter->escape(self::ISSUER_SIRET)}</p>
      </address>
    </section>

    <section class="section-card" aria-labelledby="recipient-title">
      <h2 id="recipient-title">Destinataire</h2>
      <address>
        <p><strong>{$customerName}</strong></p>
        {$customerCompany}
        {$customerAddress}
        {$customerEmail}
      </address>
    </section>

    <section aria-labelledby="items-title">
      <h2 id="items-title">Détail du devis</h2>
      <table>
        <caption>Articles et prestations du devis</caption>
        <thead>
          <tr>
            <th scope="col">Article</th>
            <th scope="col">Description</th>
            <th scope="col">Quantité</th>
            <th scope="col" class="num">Prix unitaire HT</th>
            <th scope="col" class="num">TVA</th>
            <th scope="col" class="num">Remise</th>
            <th scope="col" class="num">Total HT</th>
          </tr>
        </thead>
        <tbody>
          {$rows}
        </tbody>
      </table>
    </section>

    <section aria-labelledby="totals-title">
      <h2 id="totals-title">Totaux</h2>
      <table class="totals-table">
        <tbody>
          <tr>
            <th scope="row">Total HT</th>
            <td class="num">{$this->formatter->money($totals['totalHt'])}</td>
          </tr>
          <tr>
            <th scope="row">TVA</th>
            <td class="num">{$this->formatter->money($totals['totalVat'])}</td>
          </tr>
          <tr>
            <th scope="row">Total TTC</th>
            <td class="num">{$this->formatter->money($totals['totalTtc'])}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="section-card terms" aria-labelledby="terms-title">
      <h2 id="terms-title">Conditions générales du devis</h2>
      {$conditions}
    </section>
  </main>
</body>
</html>
HTML;
    }

    private function formatConditions(string $value): string { return $this->formatter->paragraphsFromLines($value, true); }

    private function formatMultilineAddress(string $value): string { return $this->formatter->paragraphsFromLines($value); }
}
