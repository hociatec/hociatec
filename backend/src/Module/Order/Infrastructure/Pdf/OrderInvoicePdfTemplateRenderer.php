<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final class OrderInvoicePdfTemplateRenderer
{
    private readonly OrderInvoicePdfHeaderSection $header;
    private readonly OrderInvoicePdfMetadataSection $metadata;
    private readonly OrderInvoicePdfTableSections $tables;
    private readonly OrderInvoicePdfFooterSection $footer;

    public function __construct(private readonly PdfHtmlFormatter $formatter)
    {
        $this->header = new OrderInvoicePdfHeaderSection($formatter);
        $this->metadata = new OrderInvoicePdfMetadataSection($formatter);
        $this->tables = new OrderInvoicePdfTableSections($formatter);
        $this->footer = new OrderInvoicePdfFooterSection($formatter);
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
    {$this->header->header($order, $invoiceNumber)}
    {$this->header->issuer()}
    {$this->header->customer($order)}
    {$this->metadata->build($order)}
    {$this->tables->items($totals['items'])}
    {$this->tables->taxes($totals['taxBreakdown'])}
    {$this->tables->totals($totals)}
    {$this->footer->build()}
  </main>
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return $this->formatter->escape($value);
    }
}
