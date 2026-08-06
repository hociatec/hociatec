<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class InvoiceIssuerProfile
{
    public const NAME = 'Hociatec';
    public const LEGAL_FORM = 'SASU';
    public const EMAIL = 'contact@hociatec.fr';
    public const ADDRESS_LINES = ['2 allée Anatoli Vaisser', '92600 Asnières-sur-Seine', 'France'];
    public const SIREN = '934 814 559';
    public const SIRET = '934 814 559 00019';
    public const VAT = 'FR93934814559';
    public const RCS = 'RCS Nanterre 934 814 559';
    public const PAYMENT_TERMS = 'Paiement à 30 jours fin de mois.';
    public const EARLY_PAYMENT_DISCOUNT = 'Aucun escompte accordé pour paiement anticipé.';
    public const LATE_PENALTY = 'Pénalités de retard exigibles au taux BCE + 10 points.';
    public const RECOVERY_FEE = 'Indemnité forfaitaire pour frais de recouvrement : 40 EUR.';
    public const OPERATION_NATURE = 'Livraison de biens';

    private function __construct()
    {
    }
}
