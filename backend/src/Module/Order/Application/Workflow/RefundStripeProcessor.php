<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\DTO\RefundProcessData;
use App\Module\Order\Application\Exception\RefundRequestNotFoundException;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Order\Application\Port\StripeRefundClient;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Shared\Application\Exception\ExternalServiceException;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class RefundStripeProcessor
{
    public function __construct(
        private RefundRequestRepositoryPort $refunds,
        private OrderCheckoutSessionRepositoryPort $payments,
        private StripeRefundClient $stripe,
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
    ) {
    }

    /** @return array{refund: RefundRequest, stripeRefund: array<string, mixed>} */
    public function process(RefundRequest $refund, RefundProcessData $data): array
    {
        if (null !== $refund->getStripeRefundId() || RefundRequest::STATUS_PROCESSED === $refund->getStatus()) {
            throw new \InvalidArgumentException('Ce remboursement a déjà été traité.');
        }
        if ($refund->getAmountCents() <= 0) {
            throw new \InvalidArgumentException('Le montant du remboursement doit être supérieur à zéro.');
        }
        if ('REMBOURSER' !== $data->confirmation) {
            throw new \InvalidArgumentException('Confirmation requise : saisis REMBOURSER pour déclencher Stripe.');
        }

        $paymentIntentId = null !== $data->paymentIntentId && '' !== trim($data->paymentIntentId)
            ? trim($data->paymentIntentId)
            : $this->findPaymentIntent($refund->getOrder());
        if (null === $paymentIntentId) {
            throw new \InvalidArgumentException('Aucun PaymentIntent Stripe trouvé pour cette commande.');
        }

        $previousStatus = $refund->getStatus();
        $refundId = (int) $refund->getId();
        $refund = $this->transactions->transactional(function () use ($refundId): RefundRequest {
            $locked = $this->refunds->findForUpdate($refundId);
            if (!$locked instanceof RefundRequest) {
                throw new RefundRequestNotFoundException('Remboursement introuvable.');
            }
            if (null !== $locked->getStripeRefundId() || in_array($locked->getStatus(), [RefundRequest::STATUS_PROCESSING, RefundRequest::STATUS_PROCESSED], true)) {
                throw new \InvalidArgumentException('Ce remboursement est déjà en cours ou a déjà été traité.');
            }
            $locked->setStatus(RefundRequest::STATUS_PROCESSING);
            $this->persistence->flush();

            return $locked;
        });

        try {
            $stripeRefund = $this->stripe->createRefund(
                [
                    'payment_intent' => $paymentIntentId,
                    'amount' => $refund->getAmountCents(),
                    'reason' => 'requested_by_customer',
                    'metadata[refund_request_id]' => (string) $refund->getId(),
                    'metadata[order_number]' => $refund->getOrder()->getNumber(),
                ],
                'refund_request:'.$refund->getId(),
            );
        } catch (ExternalServiceException|\JsonException $exception) {
            $refund->setStatus($previousStatus);
            $this->persistence->flush();
            throw new \InvalidArgumentException('Stripe a refusé le remboursement.', previous: $exception);
        }

        $stripeRefundId = is_string($stripeRefund['id'] ?? null) ? $stripeRefund['id'] : null;
        $refund->setStripeRefundId($stripeRefundId)->setStatus(RefundRequest::STATUS_PROCESSED);
        $this->persistence->flush();

        return ['refund' => $refund, 'stripeRefund' => $stripeRefund];
    }

    private function findPaymentIntent(Order $order): ?string
    {
        foreach ($this->payments->findRecentByOrderId((int) $order->getId(), 5) as $payment) {
            $paymentIntentId = $payment->getStripePaymentIntentId();
            if (null !== $paymentIntentId && '' !== $paymentIntentId) {
                return $paymentIntentId;
            }
        }

        return null;
    }
}
