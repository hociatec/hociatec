<?php

declare(strict_types=1);

namespace App\Module\Admin\Payment\Controller;

use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Service\StripeApiClient;
use App\Module\Order\Service\StripeCheckoutSessionSyncService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/payments/{paymentId}', name: 'api_admin_payments_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ShowPaymentController extends AbstractController
{
    public function __construct(
        private readonly OrderCheckoutSessionRepository $payments,
        private readonly StripeApiClient $stripe,
        private readonly StripeCheckoutSessionSyncService $stripeSync,
    ) {
    }

    public function __invoke(int $paymentId): JsonResponse
    {
        $payment = $this->payments->find($paymentId);
        if (!$payment instanceof OrderCheckoutSession) {
            return ApiResponse::error('Paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->stripeSync->syncPayment($payment);

        return ApiResponse::success([
            'payment' => $this->formatPayment($payment),
            'liveStripe' => $this->fetchLiveStripeData($payment),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPayment(OrderCheckoutSession $payment): array
    {
        return [
            'id' => $payment->getId(),
            'status' => $payment->getStatus(),
            'statusLabel' => match ($payment->getStatus()) {
                OrderCheckoutSession::STATUS_OPEN => 'Ouvert',
                OrderCheckoutSession::STATUS_PAID => 'Payé',
                OrderCheckoutSession::STATUS_EXPIRED => 'Expiré',
                OrderCheckoutSession::STATUS_FAILED => 'Échoué',
                default => $payment->getStatus(),
            },
            'stripeSessionId' => $payment->getStripeSessionId(),
            'stripePaymentIntentId' => $payment->getStripePaymentIntentId(),
            'stripePaymentStatus' => $payment->getStripePaymentStatus(),
            'stripePaymentStatusLabel' => $this->formatStripePaymentStatus($payment->getStripePaymentStatus()),
            'failureCode' => $payment->getFailureCode(),
            'failureMessage' => $payment->getFailureMessage(),
            'lastStripeEventType' => $payment->getLastStripeEventType(),
            'lastStripeEventLabel' => $this->formatStripeEventType($payment->getLastStripeEventType()),
            'customerEmail' => $payment->getCustomerEmail(),
            'customerFullName' => $payment->getCustomerFullName(),
            'shippingName' => $payment->getShippingName(),
            'shippingAddress' => $payment->getShippingAddress(),
            'shippingPostalCode' => $payment->getShippingPostalCode(),
            'shippingCity' => $payment->getShippingCity(),
            'totalPriceCents' => $payment->getTotalPriceCents(),
            'subtotalPriceCents' => $payment->getSubtotalPriceCents(),
            'discountAmountCents' => $payment->getDiscountAmountCents(),
            'currencyCode' => $payment->getCurrencyCode(),
            'orderId' => $payment->getOrderId(),
            'completedAt' => $payment->getCompletedAt()?->format(DATE_ATOM),
            'expiresAt' => $payment->getExpiresAt()?->format(DATE_ATOM),
            'createdAt' => $payment->getCreatedAt()->format(DATE_ATOM),
            'items' => $payment->getItemsPayload(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLiveStripeData(OrderCheckoutSession $payment): ?array
    {
        try {
            $session = $this->stripe->retrieveCheckoutSession($payment->getStripeSessionId());
        } catch (\Throwable $exception) {
            return [
                'error' => $exception->getMessage(),
            ];
        }

        $paymentIntentData = null;
        $paymentIntentId = is_string($session['payment_intent'] ?? null)
            ? $session['payment_intent']
            : $payment->getStripePaymentIntentId();

        if (is_string($paymentIntentId) && $paymentIntentId !== '') {
            try {
                $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);
                $paymentIntentData = [
                    'id' => $paymentIntent['id'] ?? null,
                    'status' => $paymentIntent['status'] ?? null,
                    'amount' => $paymentIntent['amount'] ?? null,
                    'currency' => $paymentIntent['currency'] ?? null,
                    'lastPaymentError' => [
                        'code' => $paymentIntent['last_payment_error']['code'] ?? null,
                        'declineCode' => $paymentIntent['last_payment_error']['decline_code'] ?? null,
                        'message' => $paymentIntent['last_payment_error']['message'] ?? null,
                        'type' => $paymentIntent['last_payment_error']['type'] ?? null,
                    ],
                ];
            } catch (\Throwable $exception) {
                $paymentIntentData = [
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return [
            'checkoutSession' => [
                'id' => $session['id'] ?? null,
                'status' => $session['status'] ?? null,
                'statusLabel' => $this->formatStripeCheckoutStatus(is_string($session['status'] ?? null) ? $session['status'] : null),
                'paymentStatus' => $session['payment_status'] ?? null,
                'paymentStatusLabel' => $this->formatStripePaymentStatus(is_string($session['payment_status'] ?? null) ? $session['payment_status'] : null),
                'paymentIntent' => $paymentIntentId,
                'customerEmail' => $session['customer_details']['email'] ?? null,
                'expiresAt' => isset($session['expires_at']) ? (new \DateTimeImmutable('@' . (int) $session['expires_at']))->format(DATE_ATOM) : null,
            ],
            'paymentIntent' => $paymentIntentData !== null ? [
                ...$paymentIntentData,
                'statusLabel' => $this->formatStripePaymentStatus(is_string($paymentIntentData['status'] ?? null) ? $paymentIntentData['status'] : null),
            ] : null,
        ];
    }

    private function formatStripePaymentStatus(?string $status): ?string
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

    private function formatStripeCheckoutStatus(?string $status): ?string
    {
        return match ($status) {
            null, '' => null,
            'open' => 'Ouverte',
            'complete' => 'Terminée',
            'expired' => 'Expirée',
            default => $status,
        };
    }

    private function formatStripeEventType(?string $eventType): ?string
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
}
