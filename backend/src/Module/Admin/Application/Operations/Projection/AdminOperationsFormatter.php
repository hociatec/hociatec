<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Admin\Application\Operations\DTO\FulfillmentOrderOutput;
use App\Module\Admin\Application\Operations\DTO\LowStockProductOutput;
use App\Module\Admin\Application\Operations\DTO\RefundOutput;
use App\Module\Admin\Application\Operations\DTO\StockMovementOutput;
use App\Module\Admin\Application\Operations\DTO\SupportRequestOutput;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Support\Domain\Entity\SupportRequest;

final readonly class AdminOperationsFormatter
{
    public function __construct(
        private AdminOperationsEmailLogFormatter $emailLogs,
        private OrderFormatter $orderFormatter,
    ) {
    }

    public function supportRequest(SupportRequest $support): SupportRequestOutput
    {
        $customer = $support->getCustomer();
        $orderId = $support->getOrderId();
        $orderNumber = $support->getOrderNumber();

        return new SupportRequestOutput(
            [
                'id' => $support->getId(),
                'status' => $support->getStatus(),
                'statusLabel' => $this->supportStatusLabel($support->getStatus()),
                'reason' => $support->getReason(),
                'subject' => $support->getSubject(),
                'message' => $support->getMessage(),
                'internalNotes' => $support->getInternalNotes(),
                'customer' => ['id' => $customer->getId(), 'name' => $customer->getFullName(), 'email' => $customer->getEmail()],
                'order' => null !== $orderId || null !== $orderNumber ? ['id' => $orderId, 'number' => $orderNumber] : null,
                'attachments' => $this->supportAttachments($support),
                'awaitingReplyFrom' => $this->supportAwaitingReplyFrom($support),
                'awaitingReplyLabel' => $this->supportAwaitingReplyLabel($support),
                'timeline' => $this->supportTimeline($support, true),
                'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $support->getUpdatedAt()->format(DATE_ATOM),
                'resolvedAt' => $support->getResolvedAt()?->format(DATE_ATOM),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function customerSupportRequest(SupportRequest $support): array
    {
        $customer = $support->getCustomer();
        $orderId = $support->getOrderId();
        $orderNumber = $support->getOrderNumber();

        return [
            'id' => $support->getId(),
            'status' => $support->getStatus(),
            'statusLabel' => $this->supportStatusLabel($support->getStatus()),
            'reason' => $support->getReason(),
            'subject' => $support->getSubject(),
            'message' => $support->getMessage(),
            'customer' => ['id' => $customer->getId(), 'name' => $customer->getFullName(), 'email' => $customer->getEmail()],
            'order' => null !== $orderId || null !== $orderNumber ? ['id' => $orderId, 'number' => $orderNumber] : null,
            'attachments' => $this->supportAttachments($support),
            'awaitingReplyFrom' => $this->supportAwaitingReplyFrom($support),
            'awaitingReplyLabel' => $this->supportAwaitingReplyLabel($support),
            'timeline' => $this->supportTimeline($support, false),
            'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $support->getUpdatedAt()->format(DATE_ATOM),
            'resolvedAt' => $support->getResolvedAt()?->format(DATE_ATOM),
        ];
    }

    public function refund(RefundRequest $refund): RefundOutput
    {
        $order = $refund->getOrder();

        return new RefundOutput([
            'id' => $refund->getId(),
            'order' => ['id' => $order->getId(), 'number' => $order->getNumber()],
            'paymentId' => $refund->getPaymentId(),
            'amountCents' => $refund->getAmountCents(),
            'currencyCode' => $refund->getCurrencyCode(),
            'status' => $refund->getStatus(),
            'reason' => $refund->getReason(),
            'internalNotes' => $refund->getInternalNotes(),
            'stripeRefundId' => $refund->getStripeRefundId(),
            'createdAt' => $refund->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $refund->getUpdatedAt()->format(DATE_ATOM),
        ]);
    }

    public function stockMovement(StockMovement $movement): StockMovementOutput
    {
        $product = $movement->getProduct();

        return new StockMovementOutput([
            'id' => $movement->getId(),
            'product' => ['id' => $product->getId(), 'name' => $product->getName(), 'sku' => $product->getSku()],
            'delta' => $movement->getDelta(),
            'stockBefore' => $movement->getStockBefore(),
            'stockAfter' => $movement->getStockAfter(),
            'reason' => $movement->getReason(),
            'note' => $movement->getNote(),
            'actor' => $movement->getActor()?->getFullName(),
            'createdAt' => $movement->getCreatedAt()->format(DATE_ATOM),
        ]);
    }

    public function lowStockProduct(Product $product): LowStockProductOutput
    {
        return new LowStockProductOutput([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'lowStockThreshold' => $product->getLowStockThreshold(),
            'category' => $product->getCategory()->getName(),
        ]);
    }

    public function fulfillmentOrder(Order $order): FulfillmentOrderOutput
    {
        $items = array_values(array_map(static fn (OrderItem $item): array => [
            'name' => $item->getProductName(),
            'sku' => $item->getProductSku(),
            'quantity' => $item->getQuantity(),
        ], $order->getItems()->toArray()));

        return new FulfillmentOrderOutput([
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'status' => $order->getStatus(),
            'statusLabel' => $this->orderFormatter->formatStatusLabel($order->getStatus()),
            'customer' => [
                'id' => $order->getUser()->getId(),
                'name' => $order->getUser()->getFullName(),
                'email' => $order->getUser()->getEmail(),
            ],
            'totalPriceCents' => $order->getTotalPriceCents(),
            'shipping' => [
                'name' => $order->getShippingName(),
                'address' => $order->getShippingAddress(),
                'postalCode' => $order->getShippingPostalCode(),
                'city' => $order->getShippingCity(),
            ],
            'delivery' => [
                'status' => $order->getDeliveryStatus(),
                'statusLabel' => $this->orderFormatter->formatDeliveryStatusLabel($order->getDeliveryStatus()),
                'carrier' => $order->getDeliveryCarrier(),
                'trackingNumber' => $order->getDeliveryTrackingNumber(),
                'trackingUrl' => $order->getDeliveryTrackingUrl(),
            ],
            'items' => $items,
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function emailLogs(): array
    {
        return $this->emailLogs->emailLogs();
    }

    public function failedCount(): int
    {
        return $this->emailLogs->failedCount();
    }

    public function supportStatusLabel(string $status): string
    {
        return match ($status) {
            SupportRequest::STATUS_NEW => 'Nouveau',
            SupportRequest::STATUS_IN_PROGRESS => 'En cours',
            SupportRequest::STATUS_WAITING_CUSTOMER => 'En attente client',
            SupportRequest::STATUS_RESOLVED => 'Résolu',
            SupportRequest::STATUS_REFUSED => 'Refusé',
            default => $status,
        };
    }

    public function emailScenarioLabel(string $scenario): string
    {
        return $this->emailLogs->emailScenarioLabel($scenario);
    }

    /** @return list<array<string, mixed>> */
    private function supportTimeline(SupportRequest $support, bool $includeInternal): array
    {
        $timeline = $support->getTimeline();
        $timeline = $this->appendLegacyTimelineEntries($support, $timeline, $includeInternal);

        $timeline = array_values(array_filter(
            $timeline,
            static function (mixed $entry) use ($includeInternal): bool {
                if (!is_array($entry)) {
                    return false;
                }

                return $includeInternal || 'internal' !== ($entry['visibility'] ?? 'customer');
            },
        ));

        return array_map(function (mixed $entry): array {
            if (!is_array($entry)) {
                return [
                    'id' => '',
                    'type' => 'message',
                    'actor' => 'system',
                    'visibility' => 'customer',
                    'authorLabel' => 'Hociatec',
                    'subject' => null,
                    'message' => null,
                    'status' => null,
                    'statusLabel' => null,
                    'attachments' => [],
                    'createdAt' => '',
                ];
            }

            $status = isset($entry['status']) && is_string($entry['status']) ? $entry['status'] : null;

            return [
                'id' => (string) ($entry['id'] ?? ''),
                'type' => (string) ($entry['type'] ?? 'message'),
                'actor' => (string) ($entry['actor'] ?? 'system'),
                'visibility' => (string) ($entry['visibility'] ?? 'customer'),
                'authorLabel' => (string) ($entry['authorLabel'] ?? 'Hociatec'),
                'subject' => isset($entry['subject']) && is_string($entry['subject']) ? $entry['subject'] : null,
                'message' => isset($entry['message']) && is_string($entry['message']) ? $entry['message'] : null,
                'status' => $status,
                'statusLabel' => null !== $status ? $this->supportStatusLabel($status) : null,
                'attachments' => array_values(array_filter(
                    is_array($entry['attachments'] ?? null) ? $entry['attachments'] : [],
                    static fn (mixed $attachment): bool => is_array($attachment) && is_string($attachment['name'] ?? null),
                )),
                'createdAt' => isset($entry['createdAt']) && is_string($entry['createdAt']) ? $entry['createdAt'] : '',
            ];
        }, $timeline);
    }

    /**
     * @param list<mixed> $timeline
     *
     * @return list<mixed>
     */
    private function appendLegacyTimelineEntries(SupportRequest $support, array $timeline, bool $includeInternal): array
    {
        $hasCustomerMessage = false;
        $hasAdminReply = false;

        foreach ($timeline as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (($entry['type'] ?? null) === 'customer_message') {
                $hasCustomerMessage = true;
            }

            if (($entry['type'] ?? null) === 'admin_reply') {
                $hasAdminReply = true;
            }
        }

        if (!$hasCustomerMessage && null !== $support->getMessage() && '' !== $support->getMessage()) {
            array_unshift($timeline, [
                'id' => 'legacy-initial-message',
                'type' => 'customer_message',
                'actor' => 'customer',
                'visibility' => 'customer',
                'authorLabel' => $support->getCustomer()->getFullName(),
                'subject' => $support->getSubject(),
                'message' => $support->getMessage(),
                'status' => null,
                'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
            ]);
        }

        if (!$hasAdminReply) {
            foreach ($this->legacyAdminRepliesFromNotes($support) as $legacyReply) {
                $timeline[] = $legacyReply;
            }
        }

        if ($includeInternal && [] === array_filter($timeline, static fn (mixed $entry): bool => is_array($entry) && ($entry['visibility'] ?? null) === 'internal')) {
            $legacyInternalNote = $this->legacyInternalNote($support);
            if (null !== $legacyInternalNote) {
                $timeline[] = $legacyInternalNote;
            }
        }

        usort($timeline, static function (mixed $left, mixed $right): int {
            $leftCreatedAt = is_array($left) && is_string($left['createdAt'] ?? null) ? $left['createdAt'] : '';
            $rightCreatedAt = is_array($right) && is_string($right['createdAt'] ?? null) ? $right['createdAt'] : '';

            return $leftCreatedAt <=> $rightCreatedAt;
        });

        return array_values($timeline);
    }

    /** @return list<array<string, mixed>> */
    private function legacyAdminRepliesFromNotes(SupportRequest $support): array
    {
        $notes = $support->getInternalNotes();
        if (null === $notes || '' === trim($notes)) {
            return [];
        }

        $entries = [];
        preg_match_all('/\[(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})\]\s+Réponse envoyée au client\s*:\s*(.+)/u', $notes, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $createdAt = \DateTimeImmutable::createFromFormat('d/m/Y H:i', trim((string) ($match[1] ?? '')));
            $subject = trim((string) ($match[2] ?? ''));

            $entries[] = [
                'id' => 'legacy-admin-reply-'.$index,
                'type' => 'admin_reply',
                'actor' => 'admin',
                'visibility' => 'customer',
                'authorLabel' => 'Équipe Hociatec',
                'subject' => '' !== $subject ? $subject : 'Réponse SAV',
                'message' => null,
                'status' => null,
                'createdAt' => ($createdAt ?: $support->getUpdatedAt())->format(DATE_ATOM),
            ];
        }

        return $entries;
    }

    /** @return array<string, mixed>|null */
    private function legacyInternalNote(SupportRequest $support): ?array
    {
        $notes = $support->getInternalNotes();
        if (null === $notes || '' === trim($notes)) {
            return null;
        }

        $sanitized = trim((string) preg_replace('/\[\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}\]\s+Réponse envoyée au client\s*:\s*.+/u', '', $notes));
        if ('' === $sanitized) {
            return null;
        }

        return [
            'id' => 'legacy-internal-note',
            'type' => 'internal_note',
            'actor' => 'admin',
            'visibility' => 'internal',
            'authorLabel' => 'Équipe Hociatec',
            'subject' => 'Note interne',
            'message' => $sanitized,
            'status' => null,
            'createdAt' => $support->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    /** @return list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}> */
    private function supportAttachments(SupportRequest $support): array
    {
        return array_values(array_filter(array_map(function (mixed $attachment) use ($support): ?array {
            if (!is_array($attachment)) {
                return null;
            }

            $name = isset($attachment['name']) && is_string($attachment['name']) ? trim($attachment['name']) : '';
            $originalName = isset($attachment['originalName']) && is_string($attachment['originalName']) ? trim($attachment['originalName']) : '';
            if ('' === $name || '' === $originalName) {
                return null;
            }

            return [
                'name' => $name,
                'originalName' => $originalName,
                'contentType' => isset($attachment['contentType']) && is_string($attachment['contentType']) ? $attachment['contentType'] : 'application/octet-stream',
                'size' => isset($attachment['size']) && is_numeric($attachment['size']) ? (int) $attachment['size'] : 0,
                'uploadedAt' => isset($attachment['uploadedAt']) && is_string($attachment['uploadedAt']) ? $attachment['uploadedAt'] : $support->getCreatedAt()->format(DATE_ATOM),
            ];
        }, $support->getAttachments()), static fn (?array $attachment): bool => null !== $attachment));
    }

    private function supportAwaitingReplyFrom(SupportRequest $support): ?string
    {
        if (in_array($support->getStatus(), [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REFUSED], true)) {
            return null;
        }

        $timeline = $this->supportTimeline($support, false);
        $lastEntry = [] !== $timeline ? $timeline[array_key_last($timeline)] : null;
        $lastActor = is_array($lastEntry) ? ($lastEntry['actor'] ?? null) : null;

        return 'admin' === $lastActor ? 'customer' : 'admin';
    }

    private function supportAwaitingReplyLabel(SupportRequest $support): ?string
    {
        return match ($this->supportAwaitingReplyFrom($support)) {
            'admin' => 'Réponse admin attendue',
            'customer' => 'Réponse client attendue',
            default => null,
        };
    }
}
