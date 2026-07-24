<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Service;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\RefundRequest;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Repository\RefundRequestRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\StripeApiClient;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RefundOperationsService
{
    public function __construct(
        private RefundRequestRepository $refunds,
        private OrderRepository $orders,
        private OrderCheckoutSessionRepository $payments,
        private StripeApiClient $stripe,
        private OrderEventLogger $events,
        private EntityManagerInterface $entityManager,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return array_map($this->formatter->refund(...), $this->refunds->findBy([], ['updatedAt' => 'DESC']));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function create(array $payload, ?User $actor): array
    {
        $order = $this->orders->find((int) ($payload['orderId'] ?? 0));
        if (!$order instanceof Order) {
            throw new OperationsResourceNotFoundException('Commande introuvable.');
        }

        $refund = new RefundRequest($order, (int) ($payload['amountCents'] ?? $order->getTotalPriceCents()), $actor);
        $refund
            ->setReason(isset($payload['reason']) ? (string) $payload['reason'] : null)
            ->setInternalNotes(isset($payload['internalNotes']) ? (string) $payload['internalNotes'] : null)
            ->setPaymentId(isset($payload['paymentId']) ? (int) $payload['paymentId'] : null)
            ->setCurrencyCode((string) ($payload['currencyCode'] ?? $order->getCurrencyCode()));

        $this->entityManager->persist($refund);
        $this->entityManager->flush();

        return $this->formatter->refund($refund);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(int $refundId, array $payload): array
    {
        $refund = $this->findRefund($refundId);
        if (isset($payload['status'])) {
            $refund->setStatus((string) $payload['status']);
        }
        if (array_key_exists('stripeRefundId', $payload)) {
            $refund->setStripeRefundId(null !== $payload['stripeRefundId'] ? (string) $payload['stripeRefundId'] : null);
        }
        if (array_key_exists('internalNotes', $payload)) {
            $refund->setInternalNotes(null !== $payload['internalNotes'] ? (string) $payload['internalNotes'] : null);
        }
        $this->entityManager->flush();

        return $this->formatter->refund($refund);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function processStripe(int $refundId, array $payload, ?User $actor): array
    {
        $refund = $this->findRefund($refundId);
        if (null !== $refund->getStripeRefundId() || RefundRequest::STATUS_PROCESSED === $refund->getStatus()) {
            throw new \InvalidArgumentException('Ce remboursement a déjà été traité.');
        }
        if ($refund->getAmountCents() <= 0) {
            throw new \InvalidArgumentException('Le montant du remboursement doit être supérieur à zéro.');
        }
        if ('REMBOURSER' !== trim((string) ($payload['confirmation'] ?? ''))) {
            throw new \InvalidArgumentException('Confirmation requise : saisis REMBOURSER pour déclencher Stripe.');
        }

        $paymentIntentId = is_string($payload['paymentIntentId'] ?? null) && '' !== trim($payload['paymentIntentId'])
            ? trim($payload['paymentIntentId'])
            : $this->findPaymentIntent($refund->getOrder());
        if (null === $paymentIntentId) {
            throw new \InvalidArgumentException('Aucun PaymentIntent Stripe trouvé pour cette commande.');
        }

        try {
            $stripeRefund = $this->stripe->createRefund([
                'payment_intent' => $paymentIntentId,
                'amount' => $refund->getAmountCents(),
                'reason' => 'requested_by_customer',
                'metadata[refund_request_id]' => (string) $refund->getId(),
                'metadata[order_number]' => $refund->getOrder()->getNumber(),
            ]);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Stripe a refusé le remboursement : '.$exception->getMessage(), previous: $exception);
        }

        $stripeRefundId = is_string($stripeRefund['id'] ?? null) ? $stripeRefund['id'] : null;
        $refund->setStripeRefundId($stripeRefundId)->setStatus(RefundRequest::STATUS_PROCESSED);
        $this->entityManager->flush();
        $this->events->log(
            $refund->getOrder(),
            $actor,
            'refund_processed',
            sprintf('Remboursement Stripe %s créé pour %s.', $stripeRefundId ?? '-', $refund->getAmountCents() / 100),
        );

        return ['item' => $this->formatter->refund($refund), 'stripeRefund' => $stripeRefund];
    }

    private function findRefund(int $refundId): RefundRequest
    {
        $refund = $this->refunds->find($refundId);
        if (!$refund instanceof RefundRequest) {
            throw new OperationsResourceNotFoundException('Remboursement introuvable.');
        }

        return $refund;
    }

    private function findPaymentIntent(Order $order): ?string
    {
        foreach ($this->payments->findBy(['orderId' => $order->getId()], ['createdAt' => 'DESC'], 5) as $payment) {
            $paymentIntentId = $payment->getStripePaymentIntentId();
            if (null !== $paymentIntentId && '' !== $paymentIntentId) {
                return $paymentIntentId;
            }
        }

        return null;
    }
}
