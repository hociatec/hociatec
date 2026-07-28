<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Shared\Pdf\AccessiblePdfRenderer;
use App\Shared\Pdf\PdfHtmlFormatter;

final class OrderInvoicePdfService
{
    private const ISSUER_NAME = 'Hociatec';
    private const ISSUER_LEGAL_FORM = 'SASU';
    private const ISSUER_EMAIL = 'contact@hociatec.fr';
    private const ISSUER_ADDRESS_LINES = [
        '2 allée Anatoli Vaisser',
        '92600 Asnières-sur-Seine',
        'France',
    ];
    private const ISSUER_SIREN = '934 814 559';
    private const ISSUER_SIRET = '934 814 559 00019';
    private const ISSUER_VAT = 'FR93934814559';
    private const ISSUER_RCS = 'RCS Nanterre 934 814 559';
    private const PAYMENT_TERMS = 'Paiement à 30 jours fin de mois.';
    private const EARLY_PAYMENT_DISCOUNT = 'Aucun escompte accordé pour paiement anticipé.';
    private const LATE_PENALTY = 'Pénalités de retard exigibles au taux BCE + 10 points.';
    private const RECOVERY_FEE = 'Indemnité forfaitaire pour frais de recouvrement : 40 EUR.';
    private const OPERATION_NATURE = 'Livraison de biens';

    public function __construct(
        private readonly AccessiblePdfRenderer $renderer,
        private readonly PdfHtmlFormatter $formatter,
    ) {
    }

    /**
     * @param array{
     *   subtotalTtcBeforeDiscount:int,
     *   totalDiscountTtc:int,
     *   totalHt:int,
     *   totalVat:int,
     *   totalTtc:int,
     *   taxBreakdown:list<array{rateBps:int, taxableCents:int, taxCents:int}>,
     *   items:list<array<string,mixed>>
     * } $totals
     */
    public function render(Order $order, array $totals): string
    {
        return $this->renderer->render(
            $this->buildHtml($order, $totals),
            'invoice',
            'Le PDF de facture n\'a pas pu être lu.',
        );
    }

    /**
     * @param array{
     *   subtotalTtcBeforeDiscount:int,
     *   totalDiscountTtc:int,
     *   totalHt:int,
     *   totalVat:int,
     *   totalTtc:int,
     *   taxBreakdown:list<array{rateBps:int, taxableCents:int, taxCents:int}>,
     *   items:list<array<string,mixed>>
     * } $totals
     */
    private function buildHtml(Order $order, array $totals): string
    {
        $invoiceNumber = $this->escape((string) $order->getInvoiceNumber());
        $issuedAt = $this->formatDate($order->getInvoicedAt()?->format('Y-m-d'));
        $saleDate = $this->formatDate($order->getCreatedAt()->format('Y-m-d'));
        $deliveryDate = $this->formatDate($order->getCreatedAt()->format('Y-m-d'));
        $dueAt = $this->formatDate($order->getInvoicedAt()?->modify('+30 days')->format('Y-m-d'));
        $orderNumber = $this->escape($order->getNumber());
        $issuerLines = implode('', array_map(
            static fn (string $line): string => '<p>'.htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>',
            self::ISSUER_ADDRESS_LINES,
        ));

        $customerDisplayName = $this->resolveCustomerName($order);
        $customerName = '' !== $customerDisplayName ? $this->escape($customerDisplayName) : '-';
        $customerCompany = $order->getBillingCompany() ? '<p>Société : '.$this->escape($order->getBillingCompany()).'</p>' : '<p>Client : particulier</p>';
        $customerSiren = $order->getBillingCompanySiren() ? '<p>SIREN client : '.$this->escape($order->getBillingCompanySiren()).'</p>' : '';
        $customerVatNumber = $order->getBillingCompanyVatNumber() ? '<p>TVA client : '.$this->escape($order->getBillingCompanyVatNumber()).'</p>' : '';
        $customerAddress = $order->getBillingAddress() ? $this->formatMultilineAddress($order->getBillingAddress()) : '';
        $customerCity = trim(sprintf('%s %s', (string) $order->getBillingPostalCode(), (string) $order->getBillingCity()));
        $customerCityHtml = '' !== $customerCity ? '<p>'.$this->escape($customerCity).'</p>' : '';
        $customerEmail = $order->getBillingEmail() ? '<p>Email : '.$this->escape($order->getBillingEmail()).'</p>' : '';
        $customerPhone = '' !== trim($order->getUser()->getPhoneNumber()) ? '<p>Téléphone : '.$this->escape($order->getUser()->getPhoneNumber()).'</p>' : '';

        $deliveryHtml = '';
        if (
            null !== $order->getShippingAddress()
            && trim((string) $order->getShippingAddress()) !== trim((string) $order->getBillingAddress())
        ) {
            $deliveryCity = trim(sprintf('%s %s', (string) $order->getShippingPostalCode(), (string) $order->getShippingCity()));
            $deliveryHtml = sprintf(
                '<dt>Adresse de livraison</dt><dd>%s%s%s</dd>',
                $order->getShippingName() ? $this->escape($order->getShippingName()).'<br>' : '',
                $this->escape($order->getShippingAddress()),
                '' !== $deliveryCity ? '<br>'.$this->escape($deliveryCity) : '',
            );
        }

        $rows = '';
        foreach ($totals['items'] as $item) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s %%</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s</td></tr>',
                $this->escape((string) $item['name']),
                $this->escape((string) $item['sku']),
                (int) $item['quantity'],
                $this->formatMoney((int) $item['unitPriceHtCents']),
                number_format(((int) $item['vatRateBps']) / 100, 2, ',', ' '),
                $this->formatMoney((int) $item['lineSubtotalHtCents']),
                $this->formatMoney((int) $item['lineVatCents']),
                $this->formatMoney((int) $item['lineTotalTtcCents']),
            );
        }

        $taxRows = '';
        foreach ($totals['taxBreakdown'] as $taxLine) {
            $taxRows .= sprintf(
                '<tr><td>%s %%</td><td class="num">%s</td><td class="num">%s</td></tr>',
                number_format(((int) $taxLine['rateBps']) / 100, 2, ',', ' '),
                $this->formatMoney((int) $taxLine['taxableCents']),
                $this->formatMoney((int) $taxLine['taxCents']),
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="author" content="Hociatec">
  <meta name="description" content="Facture électronique émise par Hociatec">
  <meta name="generator" content="WeasyPrint PDF/UA">
  <title>Facture {$invoiceNumber}</title>
  <style>
    @page { size: A4; margin: 18mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; line-height: 1.45; color: #0f172a; }
    h1, h2 { margin: 0 0 10px; color: #0f172a; }
    h1 { font-size: 22pt; }
    h2 { font-size: 13pt; margin-top: 24px; }
    p { margin: 0 0 8px; }
    address { font-style: normal; }
    address p { margin: 0 0 6px; }
    .section-card { border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; background: #fff; }
    .meta-list { margin: 0; padding: 0; }
    .meta-list dt { font-weight: 700; margin-top: 8px; }
    .meta-list dd { margin: 2px 0 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    caption { text-align: left; font-weight: 700; margin-bottom: 8px; }
    th, td { border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: top; text-align: left; }
    thead th { background: #e2e8f0; font-weight: 700; }
    .num { text-align: right; white-space: nowrap; }
    .legal-note { font-size: 10pt; color: #334155; }
  </style>
</head>
<body>
  <main>
    <header>
      <h1>Facture {$invoiceNumber}</h1>
      <p>Commande source : {$orderNumber}</p>
    </header>

    <section class="section-card">
      <h2>Émetteur</h2>
      <address>
        <p><strong>{$this->escape(self::ISSUER_NAME)}</strong></p>
        {$issuerLines}
        <p>Email : {$this->escape(self::ISSUER_EMAIL)}</p>
        <p>Forme juridique : {$this->escape(self::ISSUER_LEGAL_FORM)}</p>
        <p>SIREN : {$this->escape(self::ISSUER_SIREN)}</p>
        <p>SIRET : {$this->escape(self::ISSUER_SIRET)}</p>
        <p>{$this->escape(self::ISSUER_RCS)}</p>
        <p>TVA intracommunautaire : {$this->escape(self::ISSUER_VAT)}</p>
      </address>
    </section>

    <section class="section-card">
      <h2>Client destinataire</h2>
      <address>
        <p><strong>{$customerName}</strong></p>
        {$customerCompany}
        {$customerSiren}
        {$customerVatNumber}
        {$customerAddress}
        {$customerCityHtml}
        {$customerEmail}
        {$customerPhone}
      </address>
    </section>

    <section class="section-card">
      <h2>Mentions obligatoires</h2>
      <dl class="meta-list">
        <dt>Date d'émission</dt>
        <dd>{$issuedAt}</dd>
        <dt>Date de vente</dt>
        <dd>{$saleDate}</dd>
        <dt>Date de livraison</dt>
        <dd>{$deliveryDate}</dd>
        <dt>Date d'échéance</dt>
        <dd>{$dueAt}</dd>
        <dt>Nature de l'opération</dt>
        <dd>{$this->escape(self::OPERATION_NATURE)}</dd>
        <dt>Référence de commande</dt>
        <dd>{$orderNumber}</dd>
        <dt>Bon de commande</dt>
        <dd>{$this->escape($order->getPurchaseOrderNumber() ?? '-')}</dd>
        <dt>Devise</dt>
        <dd>{$this->escape($order->getCurrencyCode())}</dd>
        <dt>Format électronique</dt>
        <dd>{$this->escape($order->getElectronicFormat())}</dd>
        {$deliveryHtml}
      </dl>
    </section>

    <section>
      <table>
        <caption>Lignes de facture</caption>
        <thead>
          <tr>
            <th scope="col">Produit</th>
            <th scope="col">SKU</th>
            <th scope="col" class="num">Qté</th>
            <th scope="col" class="num">PU HT</th>
            <th scope="col" class="num">TVA</th>
            <th scope="col" class="num">Total HT</th>
            <th scope="col" class="num">Montant TVA</th>
            <th scope="col" class="num">Total TTC</th>
          </tr>
        </thead>
        <tbody>
          {$rows}
        </tbody>
      </table>
    </section>

    <section>
      <table>
        <caption>Ventilation TVA</caption>
        <thead>
          <tr>
            <th scope="col">Taux</th>
            <th scope="col" class="num">Base HT</th>
            <th scope="col" class="num">Montant TVA</th>
          </tr>
        </thead>
        <tbody>
          {$taxRows}
        </tbody>
      </table>
    </section>

    <section>
      <table>
        <caption>Totaux</caption>
        <tbody>
          <tr><th scope="row">Sous-total TTC avant remise</th><td class="num">{$this->formatMoney((int) $totals['subtotalTtcBeforeDiscount'])}</td></tr>
          <tr><th scope="row">Remise TTC</th><td class="num">- {$this->formatMoney((int) $totals['totalDiscountTtc'])}</td></tr>
          <tr><th scope="row">Total HT</th><td class="num">{$this->formatMoney((int) $totals['totalHt'])}</td></tr>
          <tr><th scope="row">Total TVA</th><td class="num">{$this->formatMoney((int) $totals['totalVat'])}</td></tr>
          <tr><th scope="row">Total TTC</th><td class="num"><strong>{$this->formatMoney((int) $totals['totalTtc'])}</strong></td></tr>
        </tbody>
      </table>
    </section>

    <section class="section-card legal-note">
      <h2>Paiement et pénalités</h2>
      <p>{$this->escape(self::PAYMENT_TERMS)}</p>
      <p>{$this->escape(self::EARLY_PAYMENT_DISCOUNT)}</p>
      <p>{$this->escape(self::LATE_PENALTY)}</p>
      <p>{$this->escape(self::RECOVERY_FEE)}</p>
    </section>
  </main>
</body>
</html>
HTML;
    }

    private function formatMoney(int $cents): string
    {
        return $this->formatter->money($cents);
    }

    private function formatDate(?string $date): string
    {
        return $this->formatter->date($date, true);
    }

    private function escape(string $value): string
    {
        return $this->formatter->escape($value);
    }

    private function resolveCustomerName(Order $order): string
    {
        $billingName = trim((string) $order->getBillingName());
        if ('' !== $billingName) {
            return $billingName;
        }

        return trim($order->getUser()->getFirstName().' '.$order->getUser()->getLastName());
    }

    private function formatMultilineAddress(string $value): string
    {
        return $this->formatter->paragraphsFromLines($value);
    }
}
