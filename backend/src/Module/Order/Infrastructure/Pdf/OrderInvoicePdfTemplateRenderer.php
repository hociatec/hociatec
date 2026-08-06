<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final class OrderInvoicePdfTemplateRenderer
{
    public function __construct(private readonly PdfHtmlFormatter $formatter)
    {
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
    public function buildHtml(Order $order, array $totals): string
    {
        $invoiceNumber = $this->escape((string) $order->getInvoiceNumber());
        $css = OrderInvoicePdfStyles::documentCss();

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
    {$css}
  </style>
</head>
<body>
  <main>
    {$this->headerSection($order, $invoiceNumber)}
    {$this->issuerSection()}
    {$this->customerSection($order)}
    {$this->metadataSection($order)}
    {$this->itemsSection($totals['items'])}
    {$this->taxesSection($totals['taxBreakdown'])}
    {$this->totalsSection($totals)}
    {$this->footerSection()}
  </main>
</body>
</html>
HTML;
    }

    private function headerSection(Order $order, string $invoiceNumber): string
    {
        $orderNumber = $this->escape($order->getNumber());

        return <<<HTML
<header>
  <h1>Facture {$invoiceNumber}</h1>
  <p>Commande source : {$orderNumber}</p>
</header>
HTML;
    }

    private function issuerSection(): string
    {
        $issuer = new OrderInvoicePdfIssuerBlock($this->formatter);
        $issuerLines = $issuer->addressLines();
        $issuerLegalDetails = $issuer->legalDetails();

        return <<<HTML
<section class="section-card">
  <h2>Émetteur</h2>
  <address>
    <p><strong>{$this->escape(OrderInvoiceIssuerProfile::NAME)}</strong></p>
    {$issuerLines}
    {$issuerLegalDetails}
  </address>
</section>
HTML;
    }

    private function customerSection(Order $order): string
    {
        $customer = (new OrderInvoicePdfCustomerBlock($this->formatter))->build($order);

        return <<<HTML
<section class="section-card">
  <h2>Client destinataire</h2>
  <address>
    <p><strong>{$customer['name']}</strong></p>
    {$customer['company']}
    {$customer['siren']}
    {$customer['vatNumber']}
    {$customer['address']}
    {$customer['city']}
    {$customer['email']}
    {$customer['phone']}
  </address>
</section>
HTML;
    }

    private function metadataSection(Order $order): string
    {
        $customer = (new OrderInvoicePdfCustomerBlock($this->formatter))->build($order);
        $issuedAt = $this->formatDate($order->getInvoicedAt()?->format('Y-m-d'));
        $saleDate = $this->formatDate($order->getCreatedAt()->format('Y-m-d'));
        $deliveryDate = $this->formatDate($order->getCreatedAt()->format('Y-m-d'));
        $dueAt = $this->formatDate($order->getInvoicedAt()?->modify('+30 days')->format('Y-m-d'));
        $orderNumber = $this->escape($order->getNumber());

        return <<<HTML
<section class="section-card">
  <h2>Mentions obligatoires</h2>
  <dl class="meta-list">
    <dt>Date d'émission</dt><dd>{$issuedAt}</dd>
    <dt>Date de vente</dt><dd>{$saleDate}</dd>
    <dt>Date de livraison</dt><dd>{$deliveryDate}</dd>
    <dt>Date d'échéance</dt><dd>{$dueAt}</dd>
    <dt>Nature de l'opération</dt><dd>{$this->escape(OrderInvoiceIssuerProfile::OPERATION_NATURE)}</dd>
    <dt>Référence de commande</dt><dd>{$orderNumber}</dd>
    <dt>Bon de commande</dt><dd>{$this->escape($order->getPurchaseOrderNumber() ?? '-')}</dd>
    <dt>Devise</dt><dd>{$this->escape($order->getCurrencyCode())}</dd>
    <dt>Format électronique</dt><dd>{$this->escape($order->getElectronicFormat())}</dd>
    {$customer['delivery']}
  </dl>
</section>
HTML;
    }

    /** @param list<array<string,mixed>> $items */
    private function itemsSection(array $items): string
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
    private function taxesSection(array $taxBreakdown): string
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
    private function totalsSection(array $totals): string
    {
        return <<<HTML
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
HTML;
    }

    private function footerSection(): string
    {
        return <<<HTML
<section class="section-card legal-note">
  <h2>Paiement et pénalités</h2>
  <p>{$this->escape(OrderInvoiceIssuerProfile::PAYMENT_TERMS)}</p>
  <p>{$this->escape(OrderInvoiceIssuerProfile::EARLY_PAYMENT_DISCOUNT)}</p>
  <p>{$this->escape(OrderInvoiceIssuerProfile::LATE_PENALTY)}</p>
  <p>{$this->escape(OrderInvoiceIssuerProfile::RECOVERY_FEE)}</p>
</section>
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
}
