<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Module\Order\Application\Workflow\StripeApiClient;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Shared\Application\Exception\ExternalServiceException;

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
