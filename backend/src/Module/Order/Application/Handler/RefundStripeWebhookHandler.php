<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Handler;

use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final class RefundStripeWebhookHandler
{
    public function __construct(
        private readonly RefundRequestRepositoryPort $refunds,
        private readonly UnitOfWork $persistence,
        private readonly TransactionManager $transactions,
    ) {
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array<string, mixed>
     */
    public function handle(array $object, string $type): array
    {
        $stripeRefundId = is_string($object['id'] ?? null) ? $object['id'] : null;
        $refundRequestId = isset($object['metadata']['refund_request_id'])
            ? (int) $object['metadata']['refund_request_id']
            : 0;

        if ($refundRequestId <= 0) {
            return ['type' => $type, 'refundId' => $stripeRefundId, 'localRefundId' => null];
        }

        $refund = $this->refunds->find($refundRequestId);
        if (!$refund instanceof RefundRequest) {
            return ['type' => $type, 'refundId' => $stripeRefundId, 'localRefundId' => null];
        }

        $this->transactions->transactional(function () use ($refund, $stripeRefundId, $type, $object): void {
            if (null !== $stripeRefundId) {
                $refund->setStripeRefundId($stripeRefundId);
            }

            $stripeStatus = is_string($object['status'] ?? null) ? $object['status'] : null;
            if ('refund.failed' === $type || in_array($stripeStatus, ['failed', 'canceled'], true)) {
                $refund->setStatus(RefundRequest::STATUS_REJECTED);
            } elseif ('succeeded' === $stripeStatus) {
                $refund->setStatus(RefundRequest::STATUS_PROCESSED);
            } elseif (in_array($stripeStatus, ['pending', 'requires_action'], true)) {
                $refund->setStatus(RefundRequest::STATUS_APPROVED);
            }

            $this->persistence->flush();
        });

        return ['type' => $type, 'refundId' => $stripeRefundId, 'localRefundId' => $refund->getId()];
    }
}
