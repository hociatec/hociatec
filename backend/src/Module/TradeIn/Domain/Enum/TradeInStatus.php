<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Enum;

enum TradeInStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case OFFER_SENT = 'offer_sent';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case RECEIVED = 'received';
    case INSPECTED = 'inspected';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Demande reçue',
            self::UNDER_REVIEW => 'En cours d’étude',
            self::OFFER_SENT => 'Offre envoyée',
            self::ACCEPTED => 'Offre acceptée',
            self::DECLINED => 'Offre refusée',
            self::RECEIVED => 'Matériel reçu',
            self::INSPECTED => 'Matériel inspecté',
            self::COMPLETED => 'Reprise terminée',
            self::CANCELLED => 'Demande annulée',
            self::EXPIRED => 'Offre expirée',
        };
    }
}
