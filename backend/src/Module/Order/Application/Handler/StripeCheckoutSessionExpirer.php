<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Infrastructure\Http\ExternalServiceException;
use App\Module\Order\Application\Service\StripeApiClient;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;

final readonly class StripeCheckoutSessionExpirer
{
    public function __construct(private StripeApiClient $stripe)
    {
    }

    public function expire(OrderCheckoutSession $checkout): void
    {
        try {
            $this->stripe->expireCheckoutSession($checkout->getStripeSessionId());
        } catch (ExternalServiceException|\JsonException) {
            // Stripe may already have completed or expired the session.
        }
    }
}
