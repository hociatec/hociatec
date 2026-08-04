<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Resolver;

use App\Infrastructure\Http\ExternalServiceException;
use App\Module\Order\Application\Service\StripeApiClient;

final readonly class StripePaymentFailureResolver
{
    public function __construct(private StripeApiClient $stripe)
    {
    }

    /**
     * @param array<string, mixed> $paymentIntent
     *
     * @return array{0:string|null,1:string|null,2:string|null}
     */
    public function fromPayload(array $paymentIntent): array
    {
        $paymentStatus = is_string($paymentIntent['status'] ?? null) ? $paymentIntent['status'] : null;
        $failureCode = is_string($paymentIntent['last_payment_error']['decline_code'] ?? null)
            ? $paymentIntent['last_payment_error']['decline_code']
            : (is_string($paymentIntent['last_payment_error']['code'] ?? null)
                ? $paymentIntent['last_payment_error']['code']
                : null);
        $failureMessage = is_string($paymentIntent['last_payment_error']['message'] ?? null)
            ? $paymentIntent['last_payment_error']['message']
            : null;

        return [$failureCode, $failureMessage, $paymentStatus];
    }

    /**
     * @return array{0:string|null,1:string|null,2:string|null}
     */
    public function fetch(string $paymentIntentId): array
    {
        try {
            return $this->fromPayload($this->stripe->retrievePaymentIntent($paymentIntentId));
        } catch (ExternalServiceException|\JsonException) {
            return [null, null, null];
        }
    }
}
