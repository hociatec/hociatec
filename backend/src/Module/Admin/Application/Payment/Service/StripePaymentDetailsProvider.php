<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Payment\Service;

use App\Infrastructure\Http\ExternalServiceException;
use App\Module\Order\Application\Service\StripeApiClient;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;

final readonly class StripePaymentDetailsProvider
{
    public function __construct(
        private StripeApiClient $stripe,
        private AdminPaymentFormatter $formatter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(OrderCheckoutSession $payment): array
    {
        try {
            $session = $this->stripe->retrieveCheckoutSession($payment->getStripeSessionId());
        } catch (ExternalServiceException|\JsonException) {
            return ['error' => 'Détails Stripe indisponibles.'];
        }

        $paymentIntentId = is_string($session['payment_intent'] ?? null)
            ? $session['payment_intent']
            : $payment->getStripePaymentIntentId();

        return [
            'checkoutSession' => $this->formatCheckoutSession($session, $paymentIntentId),
            'paymentIntent' => $this->fetchPaymentIntent($paymentIntentId),
        ];
    }

    /**
     * @param array<string, mixed> $session
     *
     * @return array<string, mixed>
     */
    private function formatCheckoutSession(array $session, ?string $paymentIntentId): array
    {
        $status = is_string($session['status'] ?? null) ? $session['status'] : null;
        $paymentStatus = is_string($session['payment_status'] ?? null) ? $session['payment_status'] : null;

        return [
            'id' => $session['id'] ?? null,
            'status' => $status,
            'statusLabel' => $this->formatter->stripeCheckoutStatusLabel($status),
            'paymentStatus' => $paymentStatus,
            'paymentStatusLabel' => $this->formatter->stripePaymentStatusLabel($paymentStatus),
            'paymentIntent' => $paymentIntentId,
            'customerEmail' => $session['customer_details']['email'] ?? null,
            'expiresAt' => isset($session['expires_at'])
                ? (new \DateTimeImmutable('@'.(int) $session['expires_at']))->format(DATE_ATOM)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchPaymentIntent(?string $paymentIntentId): ?array
    {
        if (null === $paymentIntentId || '' === $paymentIntentId) {
            return null;
        }

        try {
            $intent = $this->stripe->retrievePaymentIntent($paymentIntentId);
        } catch (ExternalServiceException|\JsonException) {
            return ['error' => 'Détails Stripe indisponibles.'];
        }

        $status = is_string($intent['status'] ?? null) ? $intent['status'] : null;

        return [
            'id' => $intent['id'] ?? null,
            'status' => $status,
            'statusLabel' => $this->formatter->stripePaymentStatusLabel($status),
            'amount' => $intent['amount'] ?? null,
            'currency' => $intent['currency'] ?? null,
            'lastPaymentError' => [
                'code' => $intent['last_payment_error']['code'] ?? null,
                'declineCode' => $intent['last_payment_error']['decline_code'] ?? null,
                'message' => $intent['last_payment_error']['message'] ?? null,
                'type' => $intent['last_payment_error']['type'] ?? null,
            ],
        ];
    }
}
