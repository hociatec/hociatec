<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final readonly class OrderInvoicePdfFooterSection
{
    public function __construct(private PdfHtmlFormatter $formatter)
    {
    }

    public function build(): string
    {
        return <<<HTML
<section class="section-card legal-note">
  <h2>Paiement et pénalités</h2>
  <p>{$this->formatter->escape(OrderInvoiceIssuerProfile::PAYMENT_TERMS)}</p>
  <p>{$this->formatter->escape(OrderInvoiceIssuerProfile::EARLY_PAYMENT_DISCOUNT)}</p>
  <p>{$this->formatter->escape(OrderInvoiceIssuerProfile::LATE_PENALTY)}</p>
  <p>{$this->formatter->escape(OrderInvoiceIssuerProfile::RECOVERY_FEE)}</p>
</section>
HTML;
    }
}
