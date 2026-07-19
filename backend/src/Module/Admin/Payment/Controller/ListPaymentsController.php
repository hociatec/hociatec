<?php

declare(strict_types=1);

namespace App\Module\Admin\Payment\Controller;

use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Service\StripeCheckoutSessionSyncService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/payments', name: 'api_admin_payments_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListPaymentsController extends AbstractController
{
    public function __construct(
        private readonly OrderCheckoutSessionRepository $payments,
        private readonly StripeCheckoutSessionSyncService $stripeSync,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $query = trim((string) $request->query->get('q', ''));

        if ($query === '' && (!is_string($status) || $status === '' || in_array($status, ['all', OrderCheckoutSession::STATUS_FAILED, OrderCheckoutSession::STATUS_OPEN], true))) {
            $this->stripeSync->syncRecentOpenPayments();
        }

        $qb = $this->payments->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        if (is_string($status) && $status !== '' && $status !== 'all') {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        if ($query !== '') {
            $qb->andWhere('p.customerEmail LIKE :q OR p.customerFullName LIKE :q OR p.stripeSessionId LIKE :q OR p.stripePaymentIntentId LIKE :q')
                ->setParameter('q', '%' . $query . '%');
        }

        /** @var list<OrderCheckoutSession> $items */
        $items = $qb->getQuery()->getResult();

        return ApiResponse::success([
            'items' => array_map([$this, 'formatPayment'], $items),
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
            'statusLabel' => $this->formatStatus($payment->getStatus()),
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
            'totalPriceCents' => $payment->getTotalPriceCents(),
            'currencyCode' => $payment->getCurrencyCode(),
            'orderId' => $payment->getOrderId(),
            'completedAt' => $payment->getCompletedAt()?->format(DATE_ATOM),
            'expiresAt' => $payment->getExpiresAt()?->format(DATE_ATOM),
            'createdAt' => $payment->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            OrderCheckoutSession::STATUS_OPEN => 'Ouvert',
            OrderCheckoutSession::STATUS_PAID => 'Payé',
            OrderCheckoutSession::STATUS_EXPIRED => 'Expiré',
            OrderCheckoutSession::STATUS_FAILED => 'Échoué',
            default => $status,
        };
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
