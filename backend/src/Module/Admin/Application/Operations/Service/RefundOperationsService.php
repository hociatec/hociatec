<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Service;

use App\Infrastructure\Http\ExternalServiceException;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\DTO\RefundCreateData;
use App\Module\Order\Application\DTO\RefundProcessData;
use App\Module\Order\Application\DTO\RefundUpdateData;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Order\Application\Service\OrderEventLogger;
use App\Module\Order\Application\Service\StripeApiClient;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Enum\RefundStatus;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;

final readonly class RefundOperationsService
{
    public function __construct(
        private RefundRequestRepositoryPort $refunds,
        private OrderRepositoryPort $orders,
        private OrderCheckoutSessionRepositoryPort $payments,
        private StripeApiClient $stripe,
        private OrderEventLogger $events,
        private OperationsPersistence $persistence,
        private TransactionManager $transactions,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return array_map($this->formatter->refund(...), $this->refunds->findBy([], ['updatedAt' => 'DESC']));
    }

    /** @return array<string, mixed> */
    public function create(RefundCreateData $data, ?User $actor): array
    {
        $order = $this->orders->find($data->orderId);
        if (!$order instanceof Order) {
            throw new OperationsResourceNotFoundException('Commande introuvable.');
        }

        $refund = new RefundRequest($order, $data->amountCents ?? $order->getTotalPriceCents(), $actor);
        $refund
            ->setReason($data->reason)
            ->setInternalNotes($data->internalNotes)
            ->setPaymentId($data->paymentId)
            ->setCurrencyCode($data->currencyCode);

        $this->persistence->persist($refund);
        $this->persistence->commit();

        return $this->formatter->refund($refund);
    }

    /** @return array<string, mixed> */
    public function update(int $refundId, RefundUpdateData $data): array
    {
        $refund = $this->findRefund($refundId);
        if (null !== $data->status && null === RefundStatus::tryFrom($data->status)) {
            throw new \InvalidArgumentException('Statut de remboursement invalide.');
        }
        if (null !== $data->status) {
            $refund->setStatus($data->status);
        }
        if (null !== $data->stripeRefundId) {
            $refund->setStripeRefundId($data->stripeRefundId);
        }
        if (null !== $data->internalNotes) {
            $refund->setInternalNotes($data->internalNotes);
        }
        $this->persistence->commit();

        return $this->formatter->refund($refund);
    }

    /** @return array<string, mixed> */
    public function processStripe(int $refundId, RefundProcessData $data, ?User $actor): array
    {
        $refund = $this->findRefund($refundId);
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
        $refund = $this->transactions->transactional(function () use ($refundId): RefundRequest {
            $locked = $this->refunds->findForUpdate($refundId);
            if (!$locked instanceof RefundRequest) {
                throw new OperationsResourceNotFoundException('Remboursement introuvable.');
            }
            if (null !== $locked->getStripeRefundId() || in_array($locked->getStatus(), [RefundRequest::STATUS_PROCESSING, RefundRequest::STATUS_PROCESSED], true)) {
                throw new \InvalidArgumentException('Ce remboursement est déjà en cours ou a déjà été traité.');
            }
            $locked->setStatus(RefundRequest::STATUS_PROCESSING);
            $this->persistence->commit();

            return $locked;
        });

        try {
            $stripeRefund = $this->stripe->createRefund([
                'payment_intent' => $paymentIntentId,
                'amount' => $refund->getAmountCents(),
                'reason' => 'requested_by_customer',
                'metadata[refund_request_id]' => (string) $refund->getId(),
                'metadata[order_number]' => $refund->getOrder()->getNumber(),
            ]);
        } catch (ExternalServiceException|\JsonException $exception) {
            $refund->setStatus($previousStatus);
            $this->persistence->commit();
            throw new \InvalidArgumentException('Stripe a refusé le remboursement.', previous: $exception);
        }

        $stripeRefundId = is_string($stripeRefund['id'] ?? null) ? $stripeRefund['id'] : null;
        $refund->setStripeRefundId($stripeRefundId)->setStatus(RefundRequest::STATUS_PROCESSED);
        $this->persistence->commit();
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
