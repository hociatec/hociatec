<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

final class StripeCheckoutSessionStateResolver
{
    /**
     * @param array<string, mixed> $session
     */
    public function sessionStatus(array $session): ?string
    {
        return is_string($session['status'] ?? null) ? $session['status'] : null;
    }

    /**
     * @param array<string, mixed> $session
     */
    public function paymentIntentId(array $session, ?string $fallback): ?string
    {
        $paymentIntentId = is_string($session['payment_intent'] ?? null)
            ? $session['payment_intent']
            : $fallback;

        return null === $paymentIntentId || '' === $paymentIntentId ? null : $paymentIntentId;
    }

    /**
     * @param array<string, mixed> $session
     * @param array<string, mixed> $paymentIntent
     */
    public function paymentStatus(array $session, array $paymentIntent): ?string
    {
        return is_string($paymentIntent['status'] ?? null)
            ? $paymentIntent['status']
            : (is_string($session['payment_status'] ?? null) ? $session['payment_status'] : null);
    }

    /**
     * @param array<string, mixed> $paymentIntent
     *
     * @return array{code:?string,message:?string}
     */
    public function failureDetails(array $paymentIntent): array
    {
        $failureCode = null;
        if (is_string($paymentIntent['last_payment_error']['decline_code'] ?? null)) {
            $failureCode = $paymentIntent['last_payment_error']['decline_code'];
        } elseif (is_string($paymentIntent['last_payment_error']['code'] ?? null)) {
            $failureCode = $paymentIntent['last_payment_error']['code'];
        }

        $failureMessage = is_string($paymentIntent['last_payment_error']['message'] ?? null)
            ? $paymentIntent['last_payment_error']['message']
            : null;

        return [
            'code' => $failureCode,
            'message' => $failureMessage,
        ];
    }

    public function shouldExpireRemoteSession(?string $sessionStatus): bool
    {
        return 'open' === $sessionStatus;
    }
}
