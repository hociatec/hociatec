<?php

declare(strict_types=1);

namespace App\Module\Admin\Payment\Service;

use App\Module\Order\Entity\OrderCheckoutSession;

final class AdminPaymentFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(OrderCheckoutSession $payment): array
    {
        return [
            'id' => $payment->getId(),
            'status' => $payment->getStatus(),
            'statusLabel' => $this->statusLabel($payment->getStatus()),
            'stripeSessionId' => $payment->getStripeSessionId(),
            'stripePaymentIntentId' => $payment->getStripePaymentIntentId(),
            'stripePaymentStatus' => $payment->getStripePaymentStatus(),
            'stripePaymentStatusLabel' => $this->stripePaymentStatusLabel($payment->getStripePaymentStatus()),
            'failureCode' => $payment->getFailureCode(),
            'failureMessage' => $payment->getFailureMessage(),
            'lastStripeEventType' => $payment->getLastStripeEventType(),
            'lastStripeEventLabel' => $this->stripeEventLabel($payment->getLastStripeEventType()),
            'customerEmail' => $payment->getCustomerEmail(),
            'customerFullName' => $payment->getCustomerFullName(),
            'totalPriceCents' => $payment->getTotalPriceCents(),
            'currencyCode' => $payment->getCurrencyCode(),
            'orderId' => $payment->getOrderId(),
            'completedAt' => $payment->getCompletedAt()?->format(DATE_ATOM),
            'expiresAt' => $payment->getExpiresAt()?->format(DATE_ATOM),
            'createdAt' => $payment->getCreatedAt()->format(DATE_ATOM),
            'requiresAttention' => $this->requiresAttention($payment),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(OrderCheckoutSession $payment): array
    {
        return [
            ...$this->summary($payment),
            'shippingName' => $payment->getShippingName(),
            'shippingAddress' => $payment->getShippingAddress(),
            'shippingPostalCode' => $payment->getShippingPostalCode(),
            'shippingCity' => $payment->getShippingCity(),
            'subtotalPriceCents' => $payment->getSubtotalPriceCents(),
            'discountAmountCents' => $payment->getDiscountAmountCents(),
            'items' => $payment->getItemsPayload(),
        ];
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            OrderCheckoutSession::STATUS_OPEN => 'Ouvert',
            OrderCheckoutSession::STATUS_PAID => 'Payé',
            OrderCheckoutSession::STATUS_EXPIRED => 'Expiré',
            OrderCheckoutSession::STATUS_FAILED => 'Échoué',
            default => $status,
        };
    }

    public function stripePaymentStatusLabel(?string $status): ?string
    {
        return match ($status) {
            null, '' => null,
            'paid' => 'Payé',
            'unpaid' => 'Non payé',
            'no_payment_required' => 'Aucun paiement requis',
            'requires_payment_method' => 'Moyen de paiement requis',
            'requires_confirmation' => 'Confirmation requise',
            'requires_action' => 'Action requise',
            'processing' => 'En cours de traitement',
            'succeeded' => 'Réussi',
            'canceled' => 'Annulé',
            default => $status,
        };
    }

    public function stripeCheckoutStatusLabel(?string $status): ?string
    {
        return match ($status) {
            null, '' => null,
            'open' => 'Ouverte',
            'complete' => 'Terminée',
            'expired' => 'Expirée',
            default => $status,
        };
    }

    public function stripeEventLabel(?string $eventType): ?string
    {
        return match ($eventType) {
            null, '' => null,
            'checkout.session.completed' => 'Session de paiement finalisée',
            'checkout.session.async_payment_succeeded' => 'Paiement asynchrone confirmé',
            'checkout.session.async_payment_failed' => 'Paiement asynchrone échoué',
            'checkout.session.expired' => 'Session de paiement expirée',
            'payment_intent.payment_failed' => 'Paiement refusé',
            default => $eventType,
        };
    }

    private function requiresAttention(OrderCheckoutSession $payment): bool
    {
        return in_array($payment->getStatus(), [
            OrderCheckoutSession::STATUS_FAILED,
            OrderCheckoutSession::STATUS_EXPIRED,
        ], true) || (OrderCheckoutSession::STATUS_PAID === $payment->getStatus() && null === $payment->getOrderId());
    }
}
