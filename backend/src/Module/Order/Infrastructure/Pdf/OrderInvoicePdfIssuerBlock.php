<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final readonly class OrderInvoicePdfIssuerBlock
{
    public function __construct(private PdfHtmlFormatter $formatter)
    {
    }

    public function addressLines(): string
    {
        return implode('', array_map(
            fn (string $line): string => '<p>'.$this->formatter->escape($line).'</p>',
            OrderInvoiceIssuerProfile::ADDRESS_LINES,
        ));
    }

    public function legalDetails(): string
    {
        return sprintf(
            '<p>Email : %s</p><p>Forme juridique : %s</p><p>SIREN : %s</p><p>SIRET : %s</p><p>%s</p><p>TVA intracommunautaire : %s</p>',
            $this->formatter->escape(OrderInvoiceIssuerProfile::EMAIL),
            $this->formatter->escape(OrderInvoiceIssuerProfile::LEGAL_FORM),
            $this->formatter->escape(OrderInvoiceIssuerProfile::SIREN),
            $this->formatter->escape(OrderInvoiceIssuerProfile::SIRET),
            $this->formatter->escape(OrderInvoiceIssuerProfile::RCS),
            $this->formatter->escape(OrderInvoiceIssuerProfile::VAT),
        );
    }
}
