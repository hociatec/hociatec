<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

enum StripeCheckoutEventType: string
{
    case CheckoutSessionCompleted = 'checkout.session.completed';
    case CheckoutSessionAsyncPaymentSucceeded = 'checkout.session.async_payment_succeeded';
    case CheckoutSessionExpired = 'checkout.session.expired';
    case CheckoutSessionAsyncPaymentFailed = 'checkout.session.async_payment_failed';
    case PaymentIntentPaymentFailed = 'payment_intent.payment_failed';

    public static function fromInput(string $type): self
    {
        return self::tryFrom(trim($type))
            ?? throw new \InvalidArgumentException('Type d evenement Stripe invalide.');
    }
}
