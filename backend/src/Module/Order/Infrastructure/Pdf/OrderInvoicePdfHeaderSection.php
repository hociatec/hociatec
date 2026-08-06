<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final readonly class OrderInvoicePdfHeaderSection
{
    public function __construct(private PdfHtmlFormatter $formatter)
    {
    }

    public function header(Order $order, string $invoiceNumber): string
    {
        $orderNumber = $this->formatter->escape($order->getNumber());

        return <<<HTML
<header>
  <h1>Facture {$invoiceNumber}</h1>
  <p>Commande source : {$orderNumber}</p>
</header>
HTML;
    }

    public function issuer(): string
    {
        $issuer = new OrderInvoicePdfIssuerBlock($this->formatter);
        $issuerLines = $issuer->addressLines();
        $issuerLegalDetails = $issuer->legalDetails();
        $name = $this->formatter->escape(OrderInvoiceIssuerProfile::NAME);

        return <<<HTML
<section class="section-card">
  <h2>Émetteur</h2>
  <address>
    <p><strong>{$name}</strong></p>
    {$issuerLines}
    {$issuerLegalDetails}
  </address>
</section>
HTML;
    }

    public function customer(Order $order): string
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
}
