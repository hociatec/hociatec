<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

enum StripePaymentStatus: string
{
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Failed = 'failed';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case RequiresAction = 'requires_action';
    case RequiresPaymentMethod = 'requires_payment_method';

    public static function fromInput(string $status): self
    {
        return self::tryFrom(trim($status))
            ?? throw new \InvalidArgumentException('Statut de paiement Stripe invalide.');
    }
}
