<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Shared\Application\InvoiceIssuerProfile;

final class OrderInvoiceIssuerProfile
{
    public const NAME = InvoiceIssuerProfile::NAME;
    public const LEGAL_FORM = InvoiceIssuerProfile::LEGAL_FORM;
    public const EMAIL = InvoiceIssuerProfile::EMAIL;
    public const ADDRESS_LINES = InvoiceIssuerProfile::ADDRESS_LINES;
    public const SIREN = InvoiceIssuerProfile::SIREN;
    public const SIRET = InvoiceIssuerProfile::SIRET;
    public const VAT = InvoiceIssuerProfile::VAT;
    public const RCS = InvoiceIssuerProfile::RCS;
    public const PAYMENT_TERMS = InvoiceIssuerProfile::PAYMENT_TERMS;
    public const EARLY_PAYMENT_DISCOUNT = InvoiceIssuerProfile::EARLY_PAYMENT_DISCOUNT;
    public const LATE_PENALTY = InvoiceIssuerProfile::LATE_PENALTY;
    public const RECOVERY_FEE = InvoiceIssuerProfile::RECOVERY_FEE;
    public const OPERATION_NATURE = InvoiceIssuerProfile::OPERATION_NATURE;

    private function __construct()
    {
    }
}
