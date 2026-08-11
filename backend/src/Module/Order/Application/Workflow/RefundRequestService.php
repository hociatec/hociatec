<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\DTO\RefundCreateData;
use App\Module\Order\Application\DTO\RefundUpdateData;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Enum\RefundStatus;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class RefundRequestService
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function create(Order $order, RefundCreateData $data, ?User $actor): RefundRequest
    {
        $refund = new RefundRequest($order, $data->amountCents ?? $order->getTotalPriceCents(), $actor);
        $refund
            ->setReason($data->reason)
            ->setInternalNotes($data->internalNotes)
            ->setPaymentId($data->paymentId)
            ->setCurrencyCode($data->currencyCode);

        $this->persistence->persist($refund);
        $this->persistence->flush();

        return $refund;
    }

    public function update(RefundRequest $refund, RefundUpdateData $data): RefundRequest
    {
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

        $this->persistence->flush();

        return $refund;
    }
}
