<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

use App\Shared\Application\InvoiceIssuerProfile;

final readonly class UblInvoicePaymentXml
{
    public function __construct(private UblXmlFormatter $formatter = new UblXmlFormatter())
    {
    }

    public function note(): string
    {
        return $this->formatter->xml(implode(' ', [
            InvoiceIssuerProfile::PAYMENT_TERMS,
            InvoiceIssuerProfile::EARLY_PAYMENT_DISCOUNT,
            InvoiceIssuerProfile::LATE_PENALTY,
            InvoiceIssuerProfile::RECOVERY_FEE,
        ]));
    }
}
