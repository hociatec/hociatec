<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Workflow;

use App\Module\Admin\Application\Operations\DTO\RefundOutput;
use App\Module\Admin\Application\Operations\DTO\RefundStripeProcessResult;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\DTO\RefundCreateData;
use App\Module\Order\Application\DTO\RefundProcessData;
use App\Module\Order\Application\DTO\RefundUpdateData;
use App\Module\Order\Application\Exception\RefundRequestNotFoundException;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Application\Workflow\RefundRequestService;
use App\Module\Order\Application\Workflow\RefundStripeProcessor;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\User\Domain\Entity\User;

final class RefundOperationsService
{
    public function __construct(
        private RefundOperationPorts $ports,
        private RefundRequestService $refundService,
        private RefundStripeProcessor $stripeProcessor,
        private OrderEventLogger $events,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /** @return list<RefundOutput> */
    public function list(int $limit = 20, int $offset = 0): array
    {
        return array_map($this->formatter->refund(...), $this->ports->refunds->findBy([], ['updatedAt' => 'DESC'], max(1, min(100, $limit)), max(0, $offset)));
    }

    public function count(): int
    {
        return $this->ports->refunds->count([]);
    }

    public function create(RefundCreateData $data, ?User $actor): RefundOutput
    {
        $order = $this->ports->orders->find($data->orderId);
        if (!$order instanceof \App\Module\Order\Domain\Entity\Order) {
            throw new OperationsResourceNotFoundException('Commande introuvable.');
        }

        $refund = $this->refundService->create($order, $data, $actor);

        return $this->formatter->refund($refund);
    }

    public function update(int $refundId, RefundUpdateData $data): RefundOutput
    {
        $refund = $this->findRefund($refundId);
        $refund = $this->refundService->update($refund, $data);

        return $this->formatter->refund($refund);
    }

    public function processStripe(int $refundId, RefundProcessData $data, ?User $actor): RefundStripeProcessResult
    {
        $refund = $this->findRefund($refundId);
        try {
            $processed = $this->stripeProcessor->process($refund, $data);
        } catch (RefundRequestNotFoundException $exception) {
            throw new OperationsResourceNotFoundException($exception->getMessage(), previous: $exception);
        }
        $refund = $processed['refund'];
        $stripeRefund = $processed['stripeRefund'];
        $this->events->log(
            $refund->getOrder(),
            $actor,
            'refund_processed',
            sprintf('Remboursement Stripe %s créé pour %s.', (string) ($stripeRefund['id'] ?? '-'), $refund->getAmountCents() / 100),
        );

        return new RefundStripeProcessResult([
            'item' => $this->formatter->refund($refund),
            'stripeRefund' => $stripeRefund,
        ]);
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
