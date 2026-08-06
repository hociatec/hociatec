<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Workflow;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\DTO\RefundCreateData;
use App\Module\Order\Application\DTO\RefundProcessData;
use App\Module\Order\Application\DTO\RefundUpdateData;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Enum\RefundStatus;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;

final class RefundOperationsService
{
    private readonly RefundStripeProcessor $stripeProcessor;

    public function __construct(
        private RefundOperationPorts $ports,
        private OrderEventLogger $events,
        private OperationsPersistence $persistence,
        TransactionManager $transactions,
        private AdminOperationsFormatter $formatter,
        ?RefundStripeProcessor $stripeProcessor = null,
    ) {
        $this->stripeProcessor = $stripeProcessor ?? new RefundStripeProcessor($ports, $persistence, $transactions);
    }

    /** @return list<array<string, mixed>> */
    public function list(int $limit = 20, int $offset = 0): array
    {
        return array_map($this->formatter->refund(...), $this->ports->refunds->findBy([], ['updatedAt' => 'DESC'], max(1, min(100, $limit)), max(0, $offset)));
    }

    public function count(): int
    {
        return $this->ports->refunds->count([]);
    }

    /** @return array<string, mixed> */
    public function create(RefundCreateData $data, ?User $actor): array
    {
        $order = $this->ports->orders->find($data->orderId);
        if (!$order instanceof \App\Module\Order\Domain\Entity\Order) {
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
        $processed = $this->stripeProcessor->process($refund, $data);
        $refund = $processed['refund'];
        $stripeRefund = $processed['stripeRefund'];
        $this->events->log(
            $refund->getOrder(),
            $actor,
            'refund_processed',
            sprintf('Remboursement Stripe %s créé pour %s.', (string) ($stripeRefund['id'] ?? '-'), $refund->getAmountCents() / 100),
        );

        return ['item' => $this->formatter->refund($refund), 'stripeRefund' => $stripeRefund];
    }

    private function findRefund(int $refundId): RefundRequest
    {
        $refund = $this->ports->refunds->find($refundId);
        if (!$refund instanceof RefundRequest) {
            throw new OperationsResourceNotFoundException('Remboursement introuvable.');
        }

        return $refund;
    }
}
