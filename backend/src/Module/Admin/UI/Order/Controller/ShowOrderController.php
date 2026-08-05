<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Provider\OrderIssueInspector;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}', name: 'api_admin_orders_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ShowOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly OrderEventRepositoryPort $events,
        private readonly OrderCheckoutSessionRepositoryPort $payments,
    ) {
    }

    public function __invoke(int $orderId): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $events = $this->events->findByOrder($order);
        $issueReasons = OrderIssueInspector::getOperationalIssues($order, $events);

        return ApiResponse::success([
            'order' => OrderFormatter::formatOrder($order, [], [
                'hasIssues' => [] !== $issueReasons,
                'issueReasons' => $issueReasons,
            ]),
            'payment' => $this->formatPayment($orderId),
            'events' => array_map(static fn ($event) => [
                'id' => $event->getId(),
                'type' => $event->getType(),
                'message' => $event->getMessage(),
                'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
                'actor' => [
                    'id' => $event->getActorUserId(),
                    'name' => $event->getActorName(),
                ],
            ], $events),
            'processing' => [
                'invoicePdfGenerated' => null !== $order->getInvoicePdfPath(),
                'invoiceXmlGenerated' => null !== $order->getInvoiceXmlPath(),
                'orderCreatedEmailSentAt' => $order->getOrderCreatedEmailSentAt()?->format(DATE_ATOM),
                'invoiceEmailSentAt' => $order->getInvoiceEmailSentAt()?->format(DATE_ATOM),
                'statusConfirmedEmailSentAt' => $order->getStatusConfirmedEmailSentAt()?->format(DATE_ATOM),
                'statusDeliveredEmailSentAt' => $order->getStatusDeliveredEmailSentAt()?->format(DATE_ATOM),
                'statusCancelledEmailSentAt' => $order->getStatusCancelledEmailSentAt()?->format(DATE_ATOM),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatPayment(int $orderId): ?array
    {
        $payment = $this->payments->findOneByOrderId($orderId);
        if (!$payment instanceof OrderCheckoutSession) {
            return null;
        }

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
            'stripePaymentStatus' => $payment->getStripePaymentStatus(),
            'lastStripeEventType' => $payment->getLastStripeEventType(),
            'failureCode' => $payment->getFailureCode(),
            'failureMessage' => $payment->getFailureMessage(),
            'completedAt' => $payment->getCompletedAt()?->format(DATE_ATOM),
            'expiresAt' => $payment->getExpiresAt()?->format(DATE_ATOM),
        ];
    }
}
